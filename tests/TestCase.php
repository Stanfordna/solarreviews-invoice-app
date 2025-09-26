<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    protected $seed = true;
    /**
     * The following line is unnecessary, as DatabaseSeeder is the default.
     * But I will keep it here for reference and/or to use another seeder class.
     */
    // protected $seeder = \Database\Seeders\DatabaseSeeder::class;
}
