<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;

class LaradockInstallCommand extends Command {
    use GitZipHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradock:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs laradock from github. If git is found it uses git else it downloads it as zip file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        if($this->isInstalled()) {
            $this->warn("Laradock is already installed. Please use laradock:update to update the version");
            exit;
        }

        $version = $this->getLatestTagFromGithub();

        // if git is installed, download laradock through git
        if($this->isGitInstalled()) {
            $this->downloadFromGit($version);
        } else {
            // if git is not installed, download as zip file
            $this->downloadZip($version);
        }

        $this->createEnv();
    }

    private function createEnv() {
        exec("cd $this->dirname && cp env-example .env");
    }

    public function downloadFromGit($version) {
        $repourl = 'https://github.com/laradock/laradock.git';
        $this->info(__FUNCTION__." $version $repourl");
        exec("git clone -c advice.detachedHead=false -b $version $repourl >/dev/null", $output, $status);
    }
}
