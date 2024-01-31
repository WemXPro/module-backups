<?php

namespace Modules\Backups\Console\Commands;

use App\Models\Settings;
use DirectoryIterator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class BackupCommand extends Command
{

    protected $signature = 'backup {--action= : create/list/restore/delete} {--type= : panel/db/all} {--file=}';
    protected $description = 'Backup manager for Wemx';

    public const ACTIONS = [
        'create' => 'Create a new backup',
        'restore' => 'Restore the panel from the backup',
        'list' => 'View the list of backups',
        'delete' => 'Delete old backups',
    ];

    public const TYPE = [
        'panel' => 'Only panel files',
        'db' => 'Only the database',
        'all' => 'All panel and database',
    ];

    private string $panel_directory, $backup_directory, $db_directory, $db_user, $db_pass, $db_host, $db_name;

    private array $args = [];

    public function handle(): void
    {
        $this->panel_directory = base_path();

        $this->backup_directory = Settings::get('backups::path', dirname(base_path()) . '/backups/wemx');
        $this->db_directory = $this->backup_directory . '/db';

        $this->db_user = config('database.connections.mysql.username');
        $this->db_pass = config('database.connections.mysql.password');
        $this->db_host = config('database.connections.mysql.host');
        $this->db_name = config('database.connections.mysql.database');

        $this->createDir($this->backup_directory);
        $this->createDir($this->db_directory);

        $this->args['ACTIONS'] = $this->option('action') ?? $this->choice(
            'Choose an action',
            self::ACTIONS
        );

        switch ($this->args['ACTIONS']) {
            case 'create':
                $this->createBackup();
                $this->deleteOldBackups();
                break;
            case 'restore':
                $this->restoreBackup();
                break;
            case 'list':
                $this->listBackups();
                break;
            case 'delete':
                $this->deleteOldBackups();
                break;
            case 'delete-file':
                $fileName = $this->option('file');
                $type = $this->option('type');
                $this->deleteSingleFile($fileName, $type);
                break;
            default:
                break;
        }
    }

    private function createBackup(): void
    {
        $this->args['TYPE'] = $this->option('type') ?? $this->choice(
            'Choose an action',
            self::TYPE
        );
        $name = now()->format('Y-m-d-H-i-s');
        switch ($this->args['TYPE']) {
            case 'panel':
                $this->createPanelBackup($name);
                break;
            case 'db':
                $this->createDbBackup($name);
                break;
            case 'all':
                $this->createPanelBackup($name);
                $this->createDbBackup($name);
                break;
            default:
                exit;
        }
    }

    private function restoreBackup(): void
    {
        $this->args['TYPE'] = $this->option('type') ?? $this->choice(
            'Choose an action',
            self::TYPE
        );

        $backups = new DirectoryIterator($this->backup_directory);

        $file = $this->choice(
            'Select backup',
            $this->backupPrepare($backups)
        );

        switch ($this->args['TYPE']) {
            case 'panel':
                $this->restorePanelBackup($file . '.zip');
                break;
            case 'db':
                $this->restoreDbBackup($file . '.sql');
                break;
            case 'all':
                $this->restorePanelBackup($file . '.zip');
                $this->restoreDbBackup($file . '.sql');
                break;
            default:
                exit;
        }
    }

    private function listBackups(): void
    {
        $type = $this->choice(
            'Choose an type',
            [
                'panel' => 'Panel backups',
                'db' => 'Database backups',
            ]
        );

        switch ($type) {
            case 'panel':
                $backups = new DirectoryIterator($this->backup_directory);
                break;
            case 'db':
                $backups = new DirectoryIterator($this->db_directory);
                break;
            default:
                exit;
        }

        $preparedBackups = $this->backupPrepare($backups);

        if (count($preparedBackups) <= 0){
            $this->logInfo("No backups available.");
            return;
        }

        foreach ($preparedBackups as $key => $fileInfo) {
            $key++;
            $this->logInfo("{$key}) {$fileInfo}");
        }
    }

    private function createPanelBackup($name): void
    {
        try {
            if (!file_exists($this->backup_directory)) {
                mkdir($this->backup_directory, 0777, true);
            }

            $backup_file = $this->backup_directory . '/backup-' . $name . '.zip';
            $panel_directory = $this->panel_directory;

            $zip = new ZipArchive();
            $zip->open($backup_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($panel_directory),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $this->logInfo('Creating a panel backup...');
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    // Check if file is in the backup directory
                    if (Str::startsWith($filePath, $this->backup_directory)) {
                        continue;
                    }

                    $relativePath = substr($filePath, strlen($panel_directory) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
        } catch (Exception $e) {
            $this->logError('An error occurred while creating the panel backup: ' . $e->getMessage());
        }
    }

    private function createDbBackup($name): void
    {
        try {
            if (!file_exists($this->db_directory)) {
                mkdir($this->db_directory, 0777, true);
            }
            $this->logInfo('Creating a database backup...');
            $command = "mariadb-dump --user={$this->db_user} --password={$this->db_pass} --host={$this->db_host} {$this->db_name} > {$this->db_directory}/db-{$name}.sql";
            system($command);
        } catch (Exception $e) {
            $this->logError('An error occurred while creating the database backup: ' . $e->getMessage());
        }
    }

    private function restorePanelBackup($file): void
    {
        try {
            $file = $this->backup_directory . '/' . $file;
            if (file_exists($file)) {
                $this->logInfo('Restore a panel backup...');
                $zip = new ZipArchive;
                if ($zip->open($file) === true) {
                    $zip->extractTo($this->panel_directory);
                    $zip->close();
                    $this->logInfo('The panel backup has been successfully restored!');
                } else {
                    $this->logError('Failed to restore panel backup.');
                }
            } else {
                $this->logError("The backup file does not exist.");
            }
        } catch (Exception $e) {
            $this->logError('An error occurred while restoring the panel backup: ' . $e->getMessage());
        }
    }

    private function restoreDbBackup($file): void
    {
        try {
            $file = Str::replaceFirst('backup', 'db', $file);
            $file = $this->db_directory . '/' . $file;
            if (!file_exists($file)) {
                $this->logError("Database backup not found: {$file}");
                return;
            }
            $this->logInfo('Restore a database backup...');
            $command = "mysql --user={$this->db_user} --password={$this->db_pass} --host={$this->db_host} {$this->db_name} < {$file}";
            system($command);
            $this->logInfo('The database backup has been successfully restored!');
        } catch (Exception $e) {
            $this->logError('An error occurred while restoring the database backup: ' . $e->getMessage());
        }
    }

    private function backupPrepare(DirectoryIterator $backups): array
    {
        $data = [];
        foreach ($backups as $fileInfo) {
            if (!$fileInfo->isDot()) {
                if ($fileInfo->isFile()) {
                    $data[] = substr($fileInfo->getFilename(), 0, (strrpos($fileInfo->getFilename(), ".")));
                }
            }
        }
        return $data;
    }

    private function createDir($path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    private function deleteOldBackups(): void
    {
        if (Settings::get('backups::auto-remove', true)){
            $this->logInfo('Deleting old backups...');

            $panelBackups = new DirectoryIterator($this->backup_directory);
            $this->deleteOldFiles($panelBackups);

            $dbBackups = new DirectoryIterator($this->db_directory);
            $this->deleteOldFiles($dbBackups);

            $this->logInfo('Old backups have been deleted successfully.');
        }
    }

    private function deleteOldFiles(DirectoryIterator $files): void
    {
        try {
            $keepCount = Settings::get('backups::save-count', 10);
            $data = [];
            foreach ($files as $fileInfo) {
                if ($fileInfo->isFile() && !$fileInfo->isDot()) {
                    $data[] = $fileInfo->getPathname();
                }
            }
            usort($data, function ($a, $b) {
                return filemtime($b) <=> filemtime($a);
            });
            for ($i = $keepCount; $i < count($data); $i++) {
                unlink($data[$i]);
            }
        } catch (Exception $e) {
            $this->logError('An error occurred while deleting old files: ' . $e->getMessage());
        }

    }

    private function deleteSingleFile($fileName, $type): void
    {
        try {
            if (!$fileName) {
                $this->logError('File name not provided.');
                return;
            }

            $directory = $type === 'db' ? $this->db_directory : $this->backup_directory;
            $filePath = $directory . '/' . $fileName;

            if (file_exists($filePath)) {
                unlink($filePath);
                $this->logInfo("File {$filePath} has been deleted.");
            } else {
                $this->logError("File {$filePath} not found.");
            }
        } catch (Exception $e) {
            $this->logError('An error occurred while deleting a single file: ' . $e->getMessage());
        }

    }

    private function logInfo($message): void
    {
        $this->info($message);
        $this->writeToLog($message);
    }

    private function logError($message): void
    {
        $this->error($message);
        $this->writeToLog($message);
    }

    private function writeToLog($message): void
    {
        file_put_contents(storage_path('logs/backups.log'), "[" . now() . "] " . $message . PHP_EOL, FILE_APPEND);
    }


}
