<?php

declare(strict_types=1);

namespace Slendie\Auth\Console;

use Slendie\Auth\AuthManager;
use Slendie\Console\Command;

final class TokenCreateCommand extends Command
{
    protected string $signature = 'auth:token
        {email : Email do utilizador}
        {--name=cli : Nome do token}
        {--abilities=* : Abilities (repetível ou separadas por vírgula; "*" = todas)}';

    protected string $description = 'Cria um token de API para um utilizador e mostra-o uma única vez';

    public function handle(AuthManager $auth): int
    {
        $email = $this->argument('email');
        $user = is_string($email) ? $auth->provider()->retrieveByCredentials(['email' => $email]) : null;

        if ($user === null) {
            $this->error('Não existe nenhum utilizador com o email ['.(is_string($email) ? $email : '').'].');

            return self::FAILURE;
        }

        if (! method_exists($user, 'createToken')) {
            $this->error('O model do utilizador tem de usar o trait Slendie\\Auth\\HasApiTokens.');

            return self::FAILURE;
        }

        $name = $this->option('name');
        $token = $user->createToken(is_string($name) && $name !== '' ? $name : 'cli', $this->abilities());

        $this->info('Token criado. Copie-o agora: não volta a ser mostrado.');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function abilities(): array
    {
        $abilities = [];

        foreach ((array) $this->option('abilities') as $value) {
            if (! is_string($value)) {
                continue;
            }

            foreach (explode(',', $value) as $ability) {
                $ability = trim($ability);

                if ($ability !== '') {
                    $abilities[] = $ability;
                }
            }
        }

        return $abilities === [] ? ['*'] : array_values(array_unique($abilities));
    }
}
