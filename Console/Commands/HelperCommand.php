<?php

namespace Modules\Backups\Console\Commands;

use DirectoryIterator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ZipArchive;

class HelperCommand extends Command
{
    protected $signature = 'backup:helper
    {--action= : make-dirs/clear-logs/append-logs/db-export/files-export/all-export}
    {--data= : File name for logs or other data (Optional)}
    {--path= : Path of the new panel to restore the backup to (Optional)}
    {--db_name= : Name of the new database to restore the backup to (Optional)}
    {--db_host= : Host of the new database to restore the backup to (Optional)}
    {--db_user= : User of the new database to restore the backup to (Optional)}
    {--db_pass= : Password of the new database to restore the backup to (Optional)}
    ';
    protected $description = 'Backups helper command for make, delete, download, clear logs, settings and etc.';
    public string $files_path, $db_path;

    public function __construct()
    {
        parent::__construct();
        $this->files_path = settings('backups::path', dirname(base_path()) . '/backups/wemx');
        $this->db_path = $this->files_path . '/db';
    }

    public function handle(): void
    {
        switch ($this->option('action')) {
            case 'make-dirs':
                $this->makeDir();
                break;
            case 'clear-logs':
                file_put_contents(storage_path('logs/backups.log'), '');
                $this->logInfo('Backups logs cleared successfully.');
                break;
            case 'append-logs':
                if ($data = $this->option('data')) {
                    $this->writeToLog($data);
                }
                break;
            case 'files-export':
                $this->exportFiles($this->option('data') ?? $this->ask('Enter the name of the backup file to restore: '));
                break;
            case 'db-export':
                $this->exportDB($this->option('data') ?? $this->ask('Enter the name of the backup file to restore: '));
                break;
            case 'all-export':
                $data = $this->option('data') ?? $this->ask('Enter the name of the backup file to restore: ');
                $this->exportDB($data);
                $this->exportFiles($data);
                break;
            default:
                $this->logError('Invalid action.');
                break;
        }
    }



    private function exportFiles($bc_file_name): void
    {
        $new_panel_path = $this->option('path') ?? $this->ask('Enter the path of the new panel to restore the backup to: ');
        $new_panel_path = rtrim($new_panel_path, '/');
        if (!file_exists($new_panel_path)) {
            system('mkdir -p ' . $new_panel_path);
        }

        try {
            $bc_file_name = Str::replaceFirst('db', 'backup', $bc_file_name);
            $bc_file_name = Str::replaceFirst('sql', 'zip', $bc_file_name);
            $bc_file_path = $this->files_path . '/' . $bc_file_name;
            if (!file_exists($bc_file_path)) {
                $this->logError("Panel backup not found: {$bc_file_path}");
                return;
            }
            $this->logInfo('Restore a panel backup...');
            $zip = new ZipArchive;
            if ($zip->open($bc_file_path) === true) {
                $zip->extractTo($new_panel_path);
                $zip->close();
                $this->logInfo('The panel backup has been successfully restored!');
            } else {
                $this->logError('Failed to restore panel backup.');
            }
        } catch (Exception $e) {
            $this->logError('An error occurred while restoring the panel backup: ' . $e->getMessage());
        }
    }

    private function exportDB($bc_file_name): void
    {
        $db_name = $this->option('db_name') ?? $this->ask('Enter the name of the new database to restore the backup to: ');
        $db_host = $this->option('db_host') ?? $this->ask('Enter the host of the new database to restore the backup to: ', 'localhost');
        $db_user = $this->option('db_user') ?? $this->ask('Enter the user of the new database to restore the backup to: ');
        $db_pass = $this->option('db_pass') ?? $this->ask('Enter the password of the new database to restore the backup to: ');

        try {
            $bc_file_name = Str::replaceFirst('backup', 'db', $bc_file_name);
            $bc_file_name = Str::replaceFirst('zip', 'sql', $bc_file_name);
            $bc_file_name = $this->db_path . '/' . $bc_file_name;
            if (!file_exists($bc_file_name)) {
                $this->logError("Database backup not found: {$bc_file_name}");
                return;
            }
            $this->logInfo('Restore a database backup...');
            $command = "mysql --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} < {$bc_file_name}";
            system($command);
            $this->logInfo('The database backup has been successfully restored!');
        } catch (Exception $e) {
            $this->logError('An error occurred while restoring the database backup: ' . $e->getMessage());
        }
    }

    private function makeDir(): void
    {
        if (file_exists($this->files_path) === false) {
            mkdir($this->files_path, 0777, true);
            if (file_exists($this->db_path) === false) mkdir($this->db_path, 0777, true);
            $this->logInfo("Backups directory created successfully. Path: \n" . $this->files_path);
        }
    }

    private function logInfo(string $message): void
    {
        $this->info($message);
        $this->writeToLog($message);
    }

    private function logError(string $message): void
    {
        $this->error($message);
        $this->writeToLog($message);
    }

    private function writeToLog(string $message): void
    {
        file_put_contents(storage_path('logs/backups.log'), "[" . now() . "] " . $message . PHP_EOL, FILE_APPEND);
    }


}
