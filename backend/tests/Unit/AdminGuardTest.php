<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Support\AdminGuard;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->clinic = $this->createClinic('sorriso');
    tenancy()->initialize($this->clinic);
    $this->guard = new AdminGuard;
});

it('refuses removing the own access', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    expect(fn () => $this->guard->ensureCanLoseAccess($admin, $admin))->toThrow(ValidationException::class);
});

it('refuses removing the last active admin', function () {
    $lastAdmin = User::factory()->admin()->create();
    $actor = User::factory()->create();

    expect(fn () => $this->guard->ensureCanLoseAccess($lastAdmin, $actor))->toThrow(ValidationException::class);
});

it('allows removing an admin when another one remains', function () {
    $target = User::factory()->admin()->create();
    $actor = User::factory()->admin()->create();

    $this->guard->ensureCanLoseAccess($target, $actor);

    expect(true)->toBeTrue();
});

it('allows removing an already inactive admin', function () {
    $target = User::factory()->admin()->inactive()->create();
    $actor = User::factory()->admin()->create();

    $this->guard->ensureCanLoseAccess($target, $actor);

    expect(true)->toBeTrue();
});
