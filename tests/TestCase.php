<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get('database.connections.'.$connection.'.database');

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('Tests must use the in-memory SQLite database. Run php artisan config:clear before testing.');
        }

        return $app;
    }
}
