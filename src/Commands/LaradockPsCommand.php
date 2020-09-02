<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;

class LaradockPsCommand extends Command {
    use GitZipHelper;

    protected $signature = 'laradock:ps';
    protected $description = 'Lists the created containers';

    public function handle() {
        passthru("cd {$this->dirname} && docker-compose ps");
    }
}
