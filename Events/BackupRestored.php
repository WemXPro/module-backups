<?php

namespace Modules\Backups\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupRestored
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public string $filePath;

    public function __construct(string $type, string $filePath)
    {
        $this->type = $type;
        $this->filePath = $filePath;
    }
}
