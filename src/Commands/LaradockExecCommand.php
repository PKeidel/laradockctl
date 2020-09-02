<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;

class LaradockExecCommand extends Command {
    use GitZipHelper, FileHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradock:exec {container?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs \'bash\' inside a docker container';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        if(!$this->isInstalled()) {
            $this->warn("Laradock is not installed yet. Please execute laradock:install");
            exit;
        }

        $container = $this->argument('container') ?? 'php-fpm';

        $cmd = "docker-compose exec $container bash";

        $this->info("Running command: $cmd");
        sleep(1);
        passthru("cd {$this->dirname} && $cmd");
    }
}
