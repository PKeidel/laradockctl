<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;

class LaradockTailCommand extends Command {
    use GitZipHelper;

    protected $signature = 'laradock:tail';
    protected $description = 'Show and follow all log outputs';

    public function handle() {
        passthru("cd {$this->dirname} && docker-compose logs -f");
    }
}
