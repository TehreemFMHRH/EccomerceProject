<?php

namespace Webkul\Core\Console\Commands;

use Illuminate\Foundation\Console\UpCommand as BaseUpCommand;
use Webkul\Core\Models\Channel;

class UpCommand extends BaseUpCommand
{
    
    public function handle()
    {
        $this->upAllChannels();

        parent::handle();
    }

    
    protected function upAllChannels()
    {
        $this->components->info('Activating all channels.');

        return Channel::query()->update(['is_maintenance_on' => 0]);
    }
}
