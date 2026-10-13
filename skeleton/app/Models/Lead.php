<?php

declare(strict_types=1);

namespace App\Models;

use Slendie\Database\Query\Builder as QueryBuilder;
use Slendie\Model\Builder;
use Slendie\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $status
 */
class Lead extends Model
{
    /** @var list<string> */
    public const array STATUSES = ['new', 'contacted', 'qualified', 'lost'];

    /** @var list<string> */
    protected array $fillable = ['name', 'email', 'phone', 'message', 'source', 'status', 'ip', 'user_agent'];

    /** Leads filtrados por estado e/ou termo de pesquisa (nome ou email). */
    public static function filtered(?string $status = null, ?string $search = null): Builder
    {
        $lead = new static();
        $query = static::query()->latest();

        if ($status !== null && $status !== '') {
            $lead->scopeStatus($query, $status);
        }

        if ($search !== null && $search !== '') {
            $lead->scopeSearch($query, $search);
        }

        return $query;
    }

    public function scopeStatus(Builder $builder, string $status): Builder
    {
        return $builder->where('status', $status);
    }

    public function scopeNew(Builder $builder): Builder
    {
        return $builder->where('status', 'new');
    }

    public function scopeSearch(Builder $builder, string $term): Builder
    {
        return $builder->where(
            fn (QueryBuilder $q) => $q->whereLike('name', "%{$term}%")->orWhereLike('email', "%{$term}%"),
        );
    }

    public function getInitialsAttribute(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = array_map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)), array_slice($words, 0, 2));

        return implode('', $initials);
    }
}
