<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

// demo:reset runs migrate:fresh, which cannot run inside the transaction of RefreshDatabase.
pest()->extend(TestCase::class)
    ->use(DatabaseMigrations::class)
    ->in('Reset');
