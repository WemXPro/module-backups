<?php

namespace Modules\Backups\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public string $identifier;

    public function __construct(string $type, string $identifier)
    {
        $this->type = $type;
        $this->identifier = $identifier;
    }
}
