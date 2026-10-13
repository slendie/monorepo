<?php

declare(strict_types=1);

namespace Slendie\Auth\Support;

/**
 * Limitador de tentativas de login guardado em ficheiros (a framework não tem cache): `max` falhas por janela
 * de `decay` segundos para a mesma chave. Cada chave é um ficheiro JSON `{hits, expires}` em `$directory`.
 */
final class LoginThrottle
{
    public function __construct(
        private readonly string $directory,
        private readonly int $maxAttempts = 5,
        private readonly int $decaySeconds = 60,
    ) {
    }

    public function tooManyAttempts(string $key): bool
    {
        return $this->read($key)['hits'] >= $this->maxAttempts;
    }

    /** Regista uma falha; a janela conta a partir da primeira. */
    public function hit(string $key): int
    {
        $state = $this->read($key);
        $hits = $state['hits'] + 1;
        $expires = $state['hits'] === 0 ? time() + $this->decaySeconds : $state['expires'];

        $this->write($key, $hits, $expires);

        return $hits;
    }

    public function availableIn(string $key): int
    {
        return max(0, $this->read($key)['expires'] - time());
    }

    public function clear(string $key): void
    {
        $file = $this->file($key);

        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * @return array{hits: int, expires: int}
     */
    private function read(string $key): array
    {
        $file = $this->file($key);
        $contents = is_file($file) ? @file_get_contents($file) : false;
        $data = is_string($contents) ? json_decode($contents, true) : null;

        if (! is_array($data) || ! is_int($data['hits'] ?? null) || ! is_int($data['expires'] ?? null) || $data['expires'] <= time()) {
            return ['hits' => 0, 'expires' => 0];
        }

        return ['hits' => $data['hits'], 'expires' => $data['expires']];
    }

    private function write(string $key, int $hits, int $expires): void
    {
        if (! is_dir($this->directory) && ! @mkdir($this->directory, 0777, true) && ! is_dir($this->directory)) {
            return;
        }

        @file_put_contents($this->file($key), (string) json_encode(['hits' => $hits, 'expires' => $expires]), LOCK_EX);
    }

    private function file(string $key): string
    {
        return rtrim($this->directory, '/\\').DIRECTORY_SEPARATOR.sha1($key).'.json';
    }
}
