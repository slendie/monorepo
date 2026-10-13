<?php

declare(strict_types=1);

namespace Slendie\Auth\Console;

use Slendie\Auth\AuthManager;
use Slendie\Auth\Hashing\Hasher;
use Slendie\Auth\Providers\ModelUserProvider;
use Slendie\Console\Command;
use Throwable;

final class MakeUserCommand extends Command
{
    protected string $signature = 'make:user
        {--name= : Nome do utilizador}
        {--email= : Email (único)}
        {--password= : Password (por omissão pergunta, sem ecoar)}';

    protected string $description = 'Cria um utilizador (para entrar em /login)';

    public function handle(AuthManager $auth, Hasher $hasher): int
    {
        $provider = $auth->provider();

        if (! $provider instanceof ModelUserProvider) {
            $this->error('make:user só funciona com o provider "model".');

            return self::FAILURE;
        }

        $name = $this->value('name', fn (): ?string => $this->ask('Nome'));
        $email = $this->value('email', fn (): ?string => $this->ask('Email'));

        if ($name === '' || $email === '') {
            $this->error('O nome e o email são obrigatórios (use --name e --email em modo não interactivo).');

            return self::FAILURE;
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("O email [{$email}] não é válido.");

            return self::FAILURE;
        }

        if ($provider->retrieveByCredentials(['email' => $email]) !== null) {
            $this->error("Já existe um utilizador com o email [{$email}].");

            return self::FAILURE;
        }

        $password = $this->value('password', fn (): ?string => $this->secret('Password'));

        if (mb_strlen($password) < 8) {
            $this->error('A password precisa de pelo menos 8 caracteres.');

            return self::FAILURE;
        }

        try {
            $model = $provider->model();
            $model::create(['name' => $name, 'email' => $email, 'password' => $hasher->make($password)]);
        } catch (Throwable $e) {
            $this->error('Não foi possível criar o utilizador: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Utilizador [{$email}] criado.");

        return self::SUCCESS;
    }

    /**
     * Valor da opção ou, se vazio, o devolvido por $prompt (nulo em modo não interactivo).
     *
     * @param  callable(): ?string  $prompt
     */
    private function value(string $option, callable $prompt): string
    {
        $value = $this->option($option);

        if (is_string($value) && $value !== '') {
            return trim($value);
        }

        return trim((string) $prompt());
    }
}
