<?php

namespace Modules\Backups\Console\Commands;

use Illuminate\Console\Command;
use PhpParser\Node\Stmt\Switch_;

class HelperCommand extends Command
{
    protected $signature = 'backup:helper {--action= : make-dirs/clear-logs/append-logs} {--data= : file name for logs or other data}';
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
        Switch ($this->option('action')) {
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
                    break;
                }
                break;
            default:
                $this->logError('Invalid action.');
                break;
        }
    }

    public function makeDir(): void
    {
        if(file_exists($this->files_path) === false){
            mkdir($this->files_path, 0777, true);
            if(file_exists($this->db_path) === false) mkdir($this->db_path, 0777, true);
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
