<?php

use App\Domains\Identity\DTOs\UserData;
use App\Domains\Identity\Enums\UserRole;

it('derives who attends patients from the role', function (UserRole $role, bool $requested, bool $expected) {
    $data = new UserData('Nome', 'nome@clinica.app', $role, null, $requested);

    expect($data->attends())->toBe($expected);
})->with([
    'professional always attends' => [UserRole::Professional, false, true],
    'reception never attends' => [UserRole::Reception, true, false],
    'admin may attend' => [UserRole::Admin, true, true],
    'admin may not attend' => [UserRole::Admin, false, false],
]);

it('has a label for every role', function () {
    expect(UserRole::Admin->label())->toBe('Administrador')
        ->and(UserRole::Professional->label())->toBe('Profissional')
        ->and(UserRole::Reception->label())->toBe('Recepção');
});
