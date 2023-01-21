<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;
use PKeidel\Laradockctl\Env\EnvFile;

class LaradockStopCommand extends Command {
    use GitZipHelper;

    protected $signature = 'laradock:stop';
    protected $description = 'Stops all running containers';

    public function handle() {
        passthru("cd {$this->dirname} && docker compose stop");
    }
}
