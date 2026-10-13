<?php

declare(strict_types=1);

namespace Slendie\Auth\Models;

use Slendie\Auth\Authenticatable;
use Slendie\Auth\Contracts\Authenticatable as AuthenticatableContract;
use Slendie\Auth\HasApiTokens;
use Slendie\Model\Model;

/**
 * Utilizador por omissão do package. Publique-o para `app/Models/User.php` com `php slendie auth:install`.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 */
class User extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasApiTokens;

    protected ?string $table = 'users';

    /** @var list<string> */
    protected array $fillable = ['name', 'email', 'password'];

    /** @var list<string> */
    protected array $hidden = ['password', 'remember_token'];
}
