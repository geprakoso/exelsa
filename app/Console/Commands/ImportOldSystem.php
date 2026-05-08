<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportOldSystem extends Command
{
    protected $signature = 'import:old-system
        {--host=127.0.0.1 : Old database host}
        {--port=3306 : Old database port}
        {--database= : Old database name}
        {--username=root : Old database username}
        {--password= : Old database password}
        {--dry-run : Show what would be imported without making changes}';

    protected $description = 'Import data from old system database (pre-rename schema) into the current system';

    protected array $columnMap = [
        'tb_pembelian_item' => [
            'hpp' => 'cost_price',
            'harga_jual' => 'selling_price',
        ],
        'tb_pembelian' => [
            'harga_jual' => 'total_amount',
        ],
        'tb_penjualan_item' => [
            'hpp' => 'cost_price',
            'harga_jual' => 'selling_price',
        ],
        'laporan_laba_rugis' => [
            'total_hpp' => 'total_cost',
        ],
    ];

    public function handle(): int
    {
        $database = $this->option('database');
        if (!$database) {
            $this->error('Please provide --database option with the old system database name.');
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        $config = [
            'driver' => 'mysql',
            'host' => $this->option('host'),
            'port' => $this->option('port'),
            'database' => $database,
            'username' => $this->option('username'),
            'password' => $this->option('password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];

        config(['database.connections.old_system' => $config]);

        try {
            DB::connection('old_system')->getPdo();
            $this->info("Connected to old system database: {$database}");
        } catch (\Exception $e) {
            $this->error("Cannot connect to old system database: {$e->getMessage()}");
            return self::FAILURE;
        }

        if (!$dryRun) {
            if (!$this->confirm('This will insert data into your current database. Continue?')) {
                $this->info('Import cancelled.');
                return self::SUCCESS;
            }
        }

        $importOrder = [
            'md_suppliers',
            'md_karyawan',
            'md_produk',
            'tb_pembelian',
            'tb_pembelian_item',
            'tb_penjualan',
            'tb_penjualan_item',
            'laporan_laba_rugis',
        ];

        $tablesImported = 0;

        foreach ($importOrder as $table) {
            if (!Schema::connection('old_system')->hasTable($table)) {
                $this->warn("Table {$table} not found in old system. Skipping.");
                continue;
            }

            $oldRows = DB::connection('old_system')->table($table)->get();
            $count = $oldRows->count();

            if ($count === 0) {
                $this->line("  {$table}: 0 rows. Skipping.");
                continue;
            }

            $mappedRows = $oldRows->map(function ($row) use ($table) {
                $row = (array) $row;
                $mappings = $this->columnMap[$table] ?? [];
                foreach ($mappings as $oldCol => $newCol) {
                    if (array_key_exists($oldCol, $row)) {
                        $row[$newCol] = $row[$oldCol];
                        unset($row[$oldCol]);
                    }
                }
                return $row;
            });

            if ($dryRun) {
                $this->info("  [DRY RUN] {$table}: would import {$count} rows");
                if ($this->output->isVerbose()) {
                    $mappings = $this->columnMap[$table] ?? [];
                    if (!empty($mappings)) {
                        $this->line('    Column mappings: ' . collect($mappings)->map(fn($to, $from) => "{$from} → {$to}")->join(', '));
                    }
                }
                continue;
            }

            $mappedRows->chunk(100)->each(function ($chunk) use ($table) {
                DB::table($table)->insertOrIgnore($chunk->toArray());
            });

            $this->info("  {$table}: imported {$count} rows");
            $tablesImported++;
        }

        if ($dryRun) {
            $this->info("\n[DRY RUN] No data was actually imported.");
        } else {
            $this->info("\nImport complete. {$tablesImported} tables processed.");
        }

        return self::SUCCESS;
    }
}