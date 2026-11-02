<?php

use App\Support\Initials;

it('takes the first and last words', function (string $name, string $expected) {
    expect(Initials::of($name))->toBe($expected);
})->with([
    ['Clínica São José', 'CJ'],
    ['Sorriso Perfeito', 'SP'],
    ['Marina', 'M'],
    ['  Ana   Souza  ', 'AS'],
]);

it('ignores professional titles', function (string $name, string $expected) {
    expect(Initials::of($name))->toBe($expected);
})->with([
    ['Dr. Carlos Mendes', 'CM'],
    ['Dra. Ana Beatriz', 'AB'],
    ['dra ana beatriz', 'AB'],
    ['Drácula Silva', 'DS'],
]);
