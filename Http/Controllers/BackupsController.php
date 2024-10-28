<?php

namespace Modules\Backups\Http\Controllers;

use App\Facades\AdminTheme;
use App\Http\Controllers\Controller;
use App\Models\Settings;
use DirectoryIterator;
use Illuminate\Support\Facades\Artisan;

class BackupsController extends Controller
{
    public string $files_path, $db_path;

    public function __construct()
    {
        $this->files_path = settings('backups::path', dirname(base_path()) . '/backups/wemx');
        $this->db_path = $this->files_path . '/db';
        if(file_exists($this->files_path) === false) Artisan::queue('backup:helper', ['--action' => 'make-dirs']);
    }

    public function index()
    {
        $files_backups = $this->backupPrepare($this->files_path);
        $db_backups = $this->backupPrepare($this->db_path);
        if (!file_exists(storage_path('logs/backups.log'))) {
            file_put_contents(storage_path('logs/backups.log'), '');
        }
        $logs = $this->getLastLines(storage_path('logs/backups.log'), 100);
        return view(AdminTheme::serviceView('backups', 'index'), compact('files_backups', 'db_backups', 'logs'));
    }

    public function clearLogs()
    {
        Artisan::queue('backup:helper', ['--action' => 'clear-logs']);
        return redirect()->back()->with('success', __('backups::messages.clear_logs_start'));
    }

    public function settings()
    {
        Settings::put('backups::path', request('path') ?? $this->files_path);
        Settings::put('backups::save-count', request('save-count') ?? 10);
        Settings::put('backups::every-hours', request('every-hours') ?? 12);
        return redirect()->back()->with('success', __('backups::messages.settings_saved'));
    }

    public function create()
    {
        Artisan::queue('backup', ['--action' => 'create', '--type' => 'all']);
        return redirect()->back()->with('success', __('backups::messages.create_backup_start'));
    }

    public function download($name)
    {
        $path = str_contains($name, '.zip') ? $this->files_path . '/' . $name : $this->db_path . '/' . $name;
        return response()->download($path);
    }

    public function delete($name)
    {
        $type = str_contains($name, '.zip') ? 'panel' : 'db';
        Artisan::queue('backup', ['--action' => 'delete-file', '--type' => $type, '--file' => $name]);
        return redirect()->back()->with('success', __('backups::messages.delete_backup_start'));
    }

    private function backupPrepare($files_path): array
    {
        $data = [];
        $totalSize = 0;
        try {
            $backups = new DirectoryIterator($files_path);
            foreach ($backups as $fileInfo) {
                if (!$fileInfo->isDot() && $fileInfo->isFile()) {
                    $data[] = [
                        'name' => $fileInfo->getFilename(),
                        'size' => $fileInfo->getSize(),
                        'path' => $fileInfo->getPath(),
                        'real_path' => $fileInfo->getRealPath(),
                        'extension' => $fileInfo->getExtension(),
                        'type' => $fileInfo->getType(),
                        'date' => now()->parse($fileInfo->getMTime())->diffForHumans(),
                        'date_raw' => $fileInfo->getMTime(),
                    ];
                    $totalSize += $fileInfo->getSize();
                }
            }
            usort($data, function ($a, $b) {
                return $b['date_raw'] <=> $a['date_raw'];
            });
            return [
                'files' => $data,
                'total_size' => $totalSize
            ];
        } catch (\Exception $e) {
            return ['files' => $data, 'total_size' => $totalSize];
        }
    }

    private function getLastLines($filePath, $lines = 10): string
    {
        $fileContent = file($filePath);
        $lastLines = array_slice($fileContent, -$lines);
        return implode("", $lastLines);
    }
}
