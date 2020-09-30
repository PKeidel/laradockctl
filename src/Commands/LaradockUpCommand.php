<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;
use PKeidel\Laradockctl\Env\EnvFile;

class LaradockUpCommand extends Command {
    use GitZipHelper;

    protected $signature = 'laradock:up {--d|detach} {--only=}';
    protected $description = 'Starts the specified containers from .env LARADOCK_CONTAINERS';

    public function handle() {

        $detach = $this->option('detach') ? '-d' : '';

        $envFile    = new EnvFile(base_path(".env"));
        $containers = $envFile->readKey('LARADOCK_CONTAINERS');

        if(($override = $this->option('only')) !== NULL)
            $containers = $override;

        $this->info("Starting containers: $containers");

        passthru("cd {$this->dirname} && docker-compose up $detach $containers");
    }
}
