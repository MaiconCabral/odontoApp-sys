<?php

use App\Domains\Tenancy\Support\ClinicUrl;

it('builds the link from the tenant record and the template', function () {
    $clinic = $this->createClinic('sorriso');

    expect(ClinicUrl::to($clinic, '/login'))->toBe('http://sorriso.localhost:3000/login')
        ->and(ClinicUrl::to($clinic, '/login', ['email' => 'a@b.app']))->toBe('http://sorriso.localhost:3000/login?email=a%40b.app');
});

it('follows the configured production template', function () {
    config(['app.frontend_url_template' => 'https://{host}', 'tenancy.central_domains' => ['odonto.app']]);

    expect(ClinicUrl::to($this->createClinic('sorriso'), '/login'))->toBe('https://sorriso.odonto.app/login');
});
