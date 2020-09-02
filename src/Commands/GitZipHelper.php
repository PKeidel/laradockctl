<?php

namespace PKeidel\Laradockctl\Commands;

trait GitZipHelper {

    public $dirname = "laradock/";

    public function downloadZip($version) {
        $this->info(__FUNCTION__);
        // https://github.com/laradock/laradock/archive/v9.5.zip
    }

    public function isGitInstalled() {
        exec('type git', $output, $status);
        return $status === 0;
    }

    public function getLatestTagFromGithub() {
        // TODO
        return 'v9.5';
        $url = "https://api.github.com/repos/laradock/laradock/tags";
        $body = file_get_contents($url);
        dd($body);
    }

    private function isInstalled() {
        return file_exists($this->dirname);
    }

}
