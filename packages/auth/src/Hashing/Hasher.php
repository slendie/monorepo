<?php

declare(strict_types=1);

namespace Slendie\Auth\Hashing;

use InvalidArgumentException;
use RuntimeException;

/**
 * Hash de passwords com `password_hash()`: bcrypt (por omissão) ou argon2id.
 *
 * Configuração (`config/auth.php` → `hashing`): `driver`, `bcrypt.rounds`, `argon2id.{memory,time,threads}`.
 */
final class Hasher
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = [])
    {
    }

    public function driver(): string
    {
        $driver = $this->config['driver'] ?? 'bcrypt';

        return is_string($driver) && $driver !== '' ? strtolower($driver) : 'bcrypt';
    }

    /**
     * @param  array<string, mixed>  $options  sobrepõe a configuração (`rounds`, `memory`, `time`, `threads`)
     */
    public function make(string $value, array $options = []): string
    {
        return password_hash($value, $this->algorithm(), $this->algorithmOptions($options));
    }

    public function check(string $value, ?string $hashed): bool
    {
        return $hashed !== null && $hashed !== '' && password_verify($value, $hashed);
    }

    /**
     * O hash foi feito com outro algoritmo ou outro custo do configurado?
     *
     * @param  array<string, mixed>  $options
     */
    public function needsRehash(string $hashed, array $options = []): bool
    {
        return password_needs_rehash($hashed, $this->algorithm(), $this->algorithmOptions($options));
    }

    /**
     * @return array{algo: int|string|null, algoName: string, options: array<string, mixed>}
     */
    public function info(string $hashed): array
    {
        return password_get_info($hashed);
    }

    /** O valor já é um hash reconhecido por `password_hash()`? */
    public function isHashed(string $value): bool
    {
        return password_get_info($value)['algo'] !== null;
    }

    private function algorithm(): string
    {
        return match ($this->driver()) {
            'bcrypt' => PASSWORD_BCRYPT,
            'argon2id' => defined('PASSWORD_ARGON2ID')
                ? PASSWORD_ARGON2ID
                : throw new RuntimeException('Esta instalação do PHP não suporta argon2id.'),
            default => throw new InvalidArgumentException("Driver de hashing [{$this->driver()}] não suportado (bcrypt ou argon2id)."),
        };
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, int>
     */
    private function algorithmOptions(array $options): array
    {
        if ($this->driver() === 'argon2id') {
            $config = $this->section('argon2id');

            return [
                'memory_cost' => $this->integer($options['memory'] ?? $config['memory'] ?? null, 65536),
                'time_cost' => $this->integer($options['time'] ?? $config['time'] ?? null, 4),
                'threads' => $this->integer($options['threads'] ?? $config['threads'] ?? null, 1),
            ];
        }

        $config = $this->section('bcrypt');

        return ['cost' => $this->integer($options['rounds'] ?? $config['rounds'] ?? null, 12)];
    }

    /**
     * @return array<string, mixed>
     */
    private function section(string $name): array
    {
        $section = $this->config[$name] ?? [];

        /** @var array<string, mixed> */
        return is_array($section) ? $section : [];
    }

    private function integer(mixed $value, int $default): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }
}
