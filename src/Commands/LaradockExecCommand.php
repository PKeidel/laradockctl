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
    protected $signature = 'laradock:exec {container=php-fpm} {--cmd=bash}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a command inside a docker container. See option --cmd';

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

        $container = $this->argument('container');

        $cmd = $this->option('cmd');

        $cmd = "docker compose exec $container $cmd";

        $this->info("Running command: $cmd");
        sleep(1);
        passthru("cd {$this->dirname} && $cmd");
    }
}
