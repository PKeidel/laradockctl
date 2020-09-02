<?php

namespace PKeidel\Laradockctl\Env;

use PKeidel\Laradockctl\Commands\FileHelper;

class EnvFile {
    use FileHelper {
        replaceOrAdd as _replaceOrAdd;
    }

    private $envfile;

    public function __construct($envfile) {
        $this->envfile = $envfile;
    }

    public function replaceOrAdd($key, $value) {
        $this->_replaceOrAdd($this->envfile, $key, $value);
    }

    public function readKey($key, $default = NULL) {
        // read from .env
        return collect(explode("\n", file_get_contents($this->envfile)))
            ->filter(function($l) {
                return strlen($l) > 0;
            })
            ->map(function($l) {
                return explode('=', $l, 2);
            })
            ->filter(function($l) {
                return count($l) > 1;
            })
            ->keyBy(function($l) {
                return $l[0];
            })
            ->map(function($l) {
                return strtolower($l[1]);
            })
            ->filter(function($l) {
                return strlen($l) > 0;
            })
            ->map(function($l) {
                return ($l[0] === '"' && substr($l, -1, 1) === '"') ? substr($l, 1, -1) : $l;
            })
            ->get($key, $default);
    }
}
