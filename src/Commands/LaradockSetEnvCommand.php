<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;

class LaradockSetEnvCommand extends Command {
    use GitZipHelper, FileHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradock:setenv {key} {value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets the value of a specified key in laradock/.env';

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

        $key     = $this->argument('key');
        $value   = $this->argument('value');

        $this->replaceOrAdd(base_path("laradock/.env"), $key, $value);
    }
}
