<?php

declare(strict_types=1);

namespace App\Models;

use Slendie\Model\Model;

/** Cópia mínima do Lead do skeleton, para o package ter o model que assume. */
class Lead extends Model
{
    /** @var list<string> */
    protected array $fillable = ['name', 'email', 'phone', 'message', 'source', 'status', 'notes'];
}
