<?php

declare(strict_types=1);

use Slendie\Auth\Support\LoginThrottle;

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'slendie-throttle-'.bin2hex(random_bytes(6));
});

afterEach(function (): void {
    foreach (glob($this->dir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
        unlink($file);
    }

    if (is_dir($this->dir)) {
        rmdir($this->dir);
    }
});

it('bloqueia à quinta falha e liberta quando a janela expira', function (): void {
    $throttle = new LoginThrottle($this->dir, 5, 60);

    foreach (range(1, 4) as $_) {
        $throttle->hit('a|1.1.1.1');
    }

    expect($throttle->tooManyAttempts('a|1.1.1.1'))->toBeFalse();

    $throttle->hit('a|1.1.1.1');

    expect($throttle->tooManyAttempts('a|1.1.1.1'))->toBeTrue()
        ->and($throttle->availableIn('a|1.1.1.1'))->toBeGreaterThan(55)->toBeLessThanOrEqual(60);

    // Simula o fim da janela.
    $file = $this->dir.DIRECTORY_SEPARATOR.sha1('a|1.1.1.1').'.json';
    file_put_contents($file, json_encode(['hits' => 5, 'expires' => time() - 1]));

    expect($throttle->tooManyAttempts('a|1.1.1.1'))->toBeFalse()
        ->and($throttle->availableIn('a|1.1.1.1'))->toBe(0);
});

it('a janela conta a partir da primeira falha (não é renovada pelas seguintes)', function (): void {
    $throttle = new LoginThrottle($this->dir, 5, 60);
    $throttle->hit('k');
    $file = $this->dir.DIRECTORY_SEPARATOR.sha1('k').'.json';
    $first = json_decode((string) file_get_contents($file), true)['expires'];

    file_put_contents($file, json_encode(['hits' => 1, 'expires' => $first - 30]));
    $throttle->hit('k');

    expect(json_decode((string) file_get_contents($file), true))->toBe(['hits' => 2, 'expires' => $first - 30]);
});

it('chaves diferentes não se afectam e clear() limpa', function (): void {
    $throttle = new LoginThrottle($this->dir, 2, 60);
    $throttle->hit('a');
    $throttle->hit('a');

    expect($throttle->tooManyAttempts('a'))->toBeTrue()
        ->and($throttle->tooManyAttempts('b'))->toBeFalse();

    $throttle->clear('a');
    $throttle->clear('nunca-existiu');

    expect($throttle->tooManyAttempts('a'))->toBeFalse()
        ->and($throttle->maxAttempts())->toBe(2);
});

it('ficheiros corrompidos contam como zero falhas e a chave não vai em claro para o disco', function (): void {
    $throttle = new LoginThrottle($this->dir);
    mkdir($this->dir, 0777, true);
    file_put_contents($this->dir.DIRECTORY_SEPARATOR.sha1('x').'.json', 'lixo{');

    expect($throttle->tooManyAttempts('x'))->toBeFalse();

    $throttle->hit('ana@x.pt|127.0.0.1');

    foreach (glob($this->dir.DIRECTORY_SEPARATOR.'*') as $file) {
        expect($file)->not->toContain('ana');
    }
});
