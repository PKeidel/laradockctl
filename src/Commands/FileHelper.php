<?php

namespace PKeidel\Laradockctl\Commands;

trait FileHelper {
    public function getFileContent($file) {
        return file_get_contents($file);
    }

    public function putFileContent($file, $content) {
        file_put_contents($file, $content);
    }

    public function replaceOrAdd($envfile, $key, $value) {

        if(is_bool($value))
            $value = $value ? 'true' : 'false';

        if(!preg_match("/^[a-zA-Z0-9-._]+$/", $value))
            $value = "\"$value\"";

        $content = $this->getFileContent($envfile);

        $found = false;
        $content = collect(explode("\n", $content))
            ->map(function($line) use($key, $value, &$found) {
                $infos = explode('=', $line, 2);
                if(count($infos) !== 2)
                    return $line;

                if($infos[0] === $key) {
                    $found = true;
                    return "$key=$value";
                }

                return "{$infos[0]}={$infos[1]}";
            })
            ->join("\n").($found ? '' : "\n$key=$value\n");

        $this->putFileContent($envfile, $content);
    }
}
