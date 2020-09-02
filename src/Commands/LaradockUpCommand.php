<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;
use PKeidel\Laradockctl\Env\EnvFile;

class LaradockUpCommand extends Command {
    use GitZipHelper;

    protected $signature = 'laradock:up';
    protected $description = 'Starts the specified containers from .env LARADOCK_CONTAINERS';

    public function handle() {
        $envFile    = new EnvFile(base_path(".env"));
        $containers = $envFile->readKey('LARADOCK_CONTAINERS');
        $this->info("Starting containers: $containers");
        passthru("cd {$this->dirname} && docker-compose up $containers");
    }
}
