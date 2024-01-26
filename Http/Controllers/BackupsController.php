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
    }

    public function index()
    {
        $files_backups = $this->backupPrepare(new DirectoryIterator($this->files_path));
        $db_backups = $this->backupPrepare(new DirectoryIterator($this->db_path));
        $logs = $this->getLastLines(storage_path('logs/backups.log'), 50);
        return view(AdminTheme::serviceView('backups', 'index'), compact('files_backups', 'db_backups', 'logs'));
    }

    public function clearLogs()
    {
        file_put_contents(storage_path('logs/backups.log'), '');
        return redirect()->back()->with('success', __('admin.success'));
    }

    public function settings()
    {
        Settings::put('backups::path', request('path') ?? $this->files_path);
        Settings::put('backups::save-count', request('save-count') ?? 10);
        Settings::put('backups::every-hours', request('every-hours') ?? 12);
        return redirect()->back()->with('success', __('responses.settings_store_success'));
    }

    public function create()
    {
        Artisan::queue('backup', ['--action' => 'create', '--type' => 'all']);
        return redirect()->back()->with('success', __('responses.backup_create_successfully'));
    }

    public function download($name)
    {
        $path = str_contains($name, '.zip') ? $this->files_path : $this->db_path;
        return response()->download($path);
    }

    public function delete($name)
    {
        $type = str_contains($name, '.zip') ? 'panel' : 'db';
        Artisan::queue('backup', ['--action' => 'delete-file', '--type' => $type, '--file' => $name]);
        return redirect()->back()->with('success', __('responses.backup_delete_successfully'));
    }

    private function backupPrepare(DirectoryIterator $backups): array
    {
        $data = [];
        $totalSize = 0;
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
    }

    private function getLastLines($filePath, $lines = 10): string
    {
        $fileContent = file($filePath);
        $lastLines = array_slice($fileContent, -$lines);
        return implode("", $lastLines);
    }
}
