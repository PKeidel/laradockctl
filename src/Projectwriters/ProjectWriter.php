<?php

namespace PKeidel\Laradockctl\Projectwriters;

abstract class ProjectWriter {

    public static function getInstance(): ProjectWriter {
        $classes = collect(scandir(dirname(__FILE__)))
            ->filter(function($f) {
                return !in_array($f, ['.', '..', basename(__FILE__)]);
            })
            ->map(function($f) {
                return substr($f, 0, -4);
            })
            ->toArray();

        foreach($classes as $class) {
            $cl = '\PKeidel\Laradockctl\Projectwriters\\'.$class;
            $c = new $cl();
            if($c->detect())
                return $c;
        }
        throw new \Exception('No Implementation for "ProjectWriter" found');
    }

    public abstract function detect();
    public abstract function writeConfigToProject($config);
}
