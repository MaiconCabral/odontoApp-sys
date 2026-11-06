<?php

namespace Tests;

use App\Domains\Agenda\Models\Procedure;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Plan;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /** @var list<Tenant> */
    private array $createdTenants = [];

    /** Deletes the clinics created by the test. */
    protected function tearDown(): void
    {
        tenancy()->end();

        foreach ($this->createdTenants as $tenant) {
            $tenant->delete();
        }

        parent::tearDown();
    }

    /** Creates the trial plan once. */
    protected function createPlan(): Plan
    {
        return Plan::query()->firstOrCreate(
            ['code' => Plan::TRIAL_CODE],
            ['name' => 'Pro', 'price_cents' => 24900, 'max_professionals' => 5],
        );
    }

    /** Creates a clinic with its database and subdomain. */
    protected function createClinic(string $slug = 'sorriso'): Tenant
    {
        $tenant = Tenant::create([
            'name' => 'Clínica '.$slug,
            'unit' => 'Unidade principal',
            'plan_id' => $this->createPlan()->id,
            'trial_ends_at' => now()->addDays(14),
        ]);
        $tenant->createDomain(['domain' => $slug]);

        return $this->createdTenants[] = $tenant;
    }

    /**
     * Sends a request the way the SPA does, from the clinic's own host.
     *
     * @param  array<string, mixed>  $data
     */
    protected function clinicRequest(string $slug, string $method, string $uri, array $data = []): TestResponse
    {
        $this->resetRequestState();

        return $this->withHeaders(['Origin' => "http://{$slug}.localhost:3000", 'Accept' => 'application/json'])
            ->json($method, "http://{$slug}.localhost/api/{$uri}", $data);
    }

    /** Id of a procedure the clinic database starts with, by name. */
    protected function procedureId(Tenant $clinic, string $name): int
    {
        return $clinic->run(fn () => Procedure::query()->where('name', $name)->value('id'));
    }

    /** Signs a user in on the clinic host, the way the SPA does. */
    protected function signIn(string $slug, User $user, string $password = 'Sorriso123'): void
    {
        $this->clinicRequest($slug, 'POST', 'login', ['email' => $user->email, 'password' => $password])->assertOk();
    }

    /**
     * Sends a request from the central domain.
     *
     * @param  array<string, mixed>  $data
     */
    protected function centralRequest(string $method, string $uri, array $data = []): TestResponse
    {
        $this->resetRequestState();

        return $this->withHeaders(['Origin' => 'http://localhost:3000', 'Accept' => 'application/json'])
            ->json($method, "http://localhost/api/{$uri}", $data);
    }

    /** Number of queries, on any connection, that the callback runs. */
    protected function countQueries(callable $send): int
    {
        return count($this->recordQueries($send));
    }

    /**
     * SQL of every query, on any connection, that the callback runs.
     *
     * @return list<string>
     */
    protected function recordQueries(callable $send): array
    {
        $this->resetRequestState();
        $queries = [];
        $recording = true;

        DB::listen(function (QueryExecuted $query) use (&$queries, &$recording) {
            if ($recording) {
                $queries[] = $query->sql;
            }
        });

        $send();
        $recording = false;

        return $queries;
    }

    /**
     * Clears what the previous request left in memory.
     * A real request starts on a fresh process. Tests share one, so what the last request resolved is dropped here.
     */
    protected function resetRequestState(): void
    {
        tenancy()->end();
        Auth::forgetGuards();
        $this->app->forgetInstance('auth.password');
        Facade::clearResolvedInstance('auth.password');
    }
}
