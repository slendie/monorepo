<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin\Support;

use Slendie\Model\Model;

/**
 * Ponto único onde o package encontra o model gerido pelo painel (`App\Models\Lead` do skeleton).
 * Para gerir outro model, copia os controllers para o projecto (ver docs/react-admin.md).
 */
final class Leads
{
    /** Classe do model gerido (alterável, p. ex. nos testes ou num provider do projecto). */
    public static string $model = 'App\\Models\\Lead';

    /** @var list<string> */
    public const array STATUSES = ['new', 'contacted', 'qualified', 'lost', 'won'];

    /** @var list<string> colunas aceites em `sort` */
    public const array SORTABLE = ['created_at', 'name', 'email', 'status'];

    public static function available(): bool
    {
        return class_exists(self::$model) && is_subclass_of(self::$model, Model::class);
    }

    /** @return class-string<Model> */
    public static function model(): string
    {
        if (! self::available()) {
            abort(500, 'O painel precisa do model '.self::$model.' (skeleton Slendie).');
        }

        /** @var class-string<Model> */
        return self::$model;
    }
}
