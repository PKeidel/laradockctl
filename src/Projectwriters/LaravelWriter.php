<?php

namespace PKeidel\Laradockctl\Projectwriters;

use PKeidel\Laradockctl\Env\EnvFile;

class LaravelWriter extends ProjectWriter {

    public function detect() {
        if(function_exists("base_path")) {
            $json = json_decode(file_get_contents(base_path('composer.json')));
            if(is_object($json) && property_exists($json, 'require') && property_exists($json->require, 'laravel/framework')) {
                return true;
            }
        }
        return false;
    }

    public function writeConfigToProject($config) {
        $envFileProject  = new EnvFile(base_path(".env"));
        foreach($config as $key => $value)
            $envFileProject->replaceOrAdd($key, $value);
    }

}
