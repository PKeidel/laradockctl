<?php

namespace PKeidel\Laradockctl\Projectreaders;

use Illuminate\Console\Command;

abstract class ProjectReader {

    /**
     * Check if current ProjectReader should be used for current project
     *
     * @return bool true if current ProjectReader should be used for current project.
     */
    public abstract function detect(): bool;
    public abstract function getNeededServies();
    public abstract function getStackName();

    public static function get(Command $command): ProjectReader {
        $classes = collect(scandir(dirname(__FILE__)))
            ->filter(function($f) {
                return !in_array($f, ['.', '..', basename(__FILE__)]);
            })
            ->map(function($f) {
                return substr($f, 0, -4);
            })
            ->toArray();
        foreach($classes as $class) {
            $cl = '\PKeidel\Laradockctl\Projectreaders\\'.$class;
            $c = new $cl($command);
            if($c->detect())
                return $c;
        }
        throw new \Exception('No Implementation for "ProjectReader" found');
    }

}
