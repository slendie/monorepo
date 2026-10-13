<?php

declare(strict_types=1);

namespace Slendie\Auth\Tests\Fixtures\Models;

use Slendie\Auth\Authenticatable;
use Slendie\Auth\Contracts\Authenticatable as AuthenticatableContract;
use Slendie\Auth\HasApiTokens;
use Slendie\Model\Model;

/** Um "User" customizado: outra tabela e outra chave primária. */
final class Member extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasApiTokens;

    protected ?string $table = 'members';

    protected string $primaryKey = 'member_id';

    /** @var list<string> */
    protected array $fillable = ['name', 'email', 'password'];
}
