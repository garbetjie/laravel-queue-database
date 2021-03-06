<?php

namespace Garbetjie\Laravel\DatabaseQueue\Console\Command;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\Console\TableCommand;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use function pathinfo;
use function str_replace;
use const PATHINFO_FILENAME;

class CreateQueueCountTableMigration extends Command
{
    /**
     * @var string
     */
    protected $signature = 'garbetjie:database-queue:table-job-counts';

    /**
     * @var string
     */
    protected $description = 'Create the migrations for storing queue counts in a separate table, and keeping them synchronised.';

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    public function handle()
    {
        // Table migration.
        $this->replaceMigration(
            'create_database_queue_counts_01_table',
            __DIR__ . '/queue_counts_table.stub'
        );

        // Trigger creation.
        $this->replaceMigration(
            'create_database_queue_counts_02_trigger',
            __DIR__ . '/queue_counts_trigger.stub'
        );

        // Populate the counts table.
        $this->replaceMigration(
            'create_database_queue_counts_03_insertion',
            __DIR__ . '/queue_counts_insert.stub'
        );

        $this->composer->dumpAutoloads();
    }

    protected function replaceMigration($migrationName, $stubPath)
    {
        $jobsTable = $this->laravel['config']['queue.connections.database.table'];
        $table = $jobsTable . '_count';


        $path = $this->laravel['migration.creator']->create($migrationName, $this->laravel->databasePath() . '/migrations');
        $stub = str_replace(
            ['{{table}}', '{{className}}', '{{jobsTable}}'],
            [$table, Str::studly($migrationName), $jobsTable],
            $this->files->get($stubPath)
        );

        $this->files->put($path, $stub);
        $this->info('Created migration ' . pathinfo($path, PATHINFO_FILENAME) . ' successfully.');
    }
}
