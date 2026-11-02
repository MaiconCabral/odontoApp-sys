<?php

namespace Database\Seeders;

use App\Domains\Tenancy\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /** Creates or updates the Free, Pro and Enterprise plans. */
    public function run(): void
    {
        $plans = [
            ['code' => 'free', 'name' => 'Free', 'price_cents' => 0, 'max_professionals' => 1],
            ['code' => 'pro', 'name' => 'Pro', 'price_cents' => 24900, 'max_professionals' => 5],
            ['code' => 'enterprise', 'name' => 'Enterprise', 'price_cents' => 59900, 'max_professionals' => null],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
