<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;

class LaradockUpdateCommand extends Command {
    use GitZipHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laradock:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates laradock. Either via git or via zip download';

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

        $version = $this->getLatestTagFromGithub();

        // create backup of .env
        $backupFile = $this->backupEnv();

        // if git is installed, update laradock through git
        if($this->isGitInstalled()) {
            $this->updateFromGit($version);
        } else {
            $this->removeFolder();
            $this->downloadZip($version);
        }

        $this->restoreEnv($backupFile);
    }

    private function backupEnv() {
        $backupFile = tempnam('/tmp', 'laradockenv_');
        $this->warn(__FUNCTION__." $backupFile");
        exec("cd $this->dirname && cp .env $backupFile");
        return $backupFile;
    }

    private function restoreEnv($backupFile) {
        exec("cd $this->dirname && cp $backupFile .env");
    }

    public function updateFromGit($version) {
        $this->info(__FUNCTION__." $version");
        exec("cd $this->dirname && git fetch && git checkout $version", $output, $status);
    }

    private function removeFolder() {
        exec("rm -rf $this->dirname", $output, $status);
    }
}
