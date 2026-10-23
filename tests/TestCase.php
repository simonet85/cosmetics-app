<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Refuse to boot a test application whose database is not a dedicated test
     * database: RefreshDatabase would otherwise wipe real data.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $default = $app['config']->get('database.default');
        $database = (string) $app['config']->get("database.connections.{$default}.database");

        if (! str_ends_with($database, '_test') && $database !== ':memory:') {
            throw new RuntimeException("Refusing to run tests against non-test database [{$database}].");
        }

        return $app;
    }
}
