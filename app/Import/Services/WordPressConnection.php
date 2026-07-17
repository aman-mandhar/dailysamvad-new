<?php

namespace App\Import\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use RuntimeException;

class WordPressConnection
{
    private ?Connection $connection = null;

    public function __construct(private readonly DatabaseManager $database) {}

    public function connection(): Connection
    {
        $configuration = config('import.profiles.wordpress.database');

        if (! is_array($configuration) || empty($configuration['database'])) {
            throw new RuntimeException('The WordPress import database profile is not configured.');
        }

        return $this->connection ??= $this->database->build($configuration);
    }

    public function table(string $table): string
    {
        return (string) config('import.profiles.wordpress.table_prefix', 'wp_').$table;
    }
}
