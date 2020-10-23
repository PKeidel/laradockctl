<?php

namespace PKeidel\Laradockctl\Commands;

trait GitZipHelper {

    public $dirname = "laradock/";

    public function downloadZip($tag) {
        $this->info(__FUNCTION__ . ' is not implemented yet. sorry.');
        // https://github.com/laradock/laradock/archive/$tag.zip
    }

    public function isGitInstalled() {
        exec('which git', $output, $status);
        return $status === 0;
    }

    public function getLatestTagFromGithub() {
        $url = "https://api.github.com/repos/laradock/laradock/releases/latest";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'php laradockctl');
        $body = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($body);
        return $json->tag_name;
    }

    private function isInstalled() {
        return file_exists($this->dirname);
    }

}
