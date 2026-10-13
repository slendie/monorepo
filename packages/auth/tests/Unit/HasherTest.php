<?php

declare(strict_types=1);

use Slendie\Auth\Hashing\Hasher;

it('faz hash com bcrypt por omissão e verifica', function (): void {
    $hasher = new Hasher(['bcrypt' => ['rounds' => 4]]);
    $hash = $hasher->make('secret');

    expect($hasher->driver())->toBe('bcrypt')
        ->and($hash)->toStartWith('$2y$04$')
        ->and($hasher->check('secret', $hash))->toBeTrue()
        ->and($hasher->check('outra', $hash))->toBeFalse()
        ->and($hasher->info($hash)['algoName'])->toBe('bcrypt')
        ->and($hasher->make('secret'))->not->toBe($hash);
});

it('check() é falso para hashes vazios ou em falta', function (): void {
    $hasher = new Hasher();

    expect($hasher->check('x', null))->toBeFalse()
        ->and($hasher->check('x', ''))->toBeFalse()
        ->and($hasher->check('x', 'não-é-um-hash'))->toBeFalse();
});

it('needsRehash detecta outro custo e outro algoritmo', function (): void {
    $weak = (new Hasher(['bcrypt' => ['rounds' => 4]]))->make('x');

    expect((new Hasher(['bcrypt' => ['rounds' => 4]]))->needsRehash($weak))->toBeFalse()
        ->and((new Hasher(['bcrypt' => ['rounds' => 5]]))->needsRehash($weak))->toBeTrue()
        ->and((new Hasher(['bcrypt' => ['rounds' => 4]]))->needsRehash($weak, ['rounds' => 6]))->toBeTrue();
});

it('usa 12 rondas por omissão', function (): void {
    $hash = (new Hasher())->make('x');

    expect(password_get_info($hash)['options']['cost'])->toBe(12);
})->group('slow');

it('suporta argon2id', function (): void {
    $hasher = new Hasher(['driver' => 'argon2id', 'argon2id' => ['memory' => 1024, 'time' => 1, 'threads' => 1]]);
    $hash = $hasher->make('secret');

    expect($hasher->driver())->toBe('argon2id')
        ->and($hash)->toStartWith('$argon2id$')
        ->and($hasher->check('secret', $hash))->toBeTrue()
        ->and($hasher->check('x', $hash))->toBeFalse()
        ->and($hasher->needsRehash($hash))->toBeFalse()
        ->and((new Hasher(['driver' => 'bcrypt']))->needsRehash($hash))->toBeTrue()
        ->and($hasher->info($hash)['algoName'])->toBe('argon2id');
})->skip(! defined('PASSWORD_ARGON2ID'), 'PHP sem argon2id');

it('um driver desconhecido é um erro claro', function (): void {
    expect(fn () => (new Hasher(['driver' => 'md5']))->make('x'))->toThrow(InvalidArgumentException::class, 'md5');
});

it('isHashed distingue hashes de texto simples', function (): void {
    $hasher = new Hasher(['bcrypt' => ['rounds' => 4]]);

    expect($hasher->isHashed($hasher->make('x')))->toBeTrue()
        ->and($hasher->isHashed('secret-123'))->toBeFalse()
        ->and($hasher->isHashed(''))->toBeFalse();
});

it('ignora configuração inválida e cai nos valores por omissão', function (): void {
    $hasher = new Hasher(['driver' => '', 'bcrypt' => 'lixo']);

    expect($hasher->driver())->toBe('bcrypt');
});
