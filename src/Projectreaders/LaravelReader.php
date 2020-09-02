<?php

namespace PKeidel\Laradockctl\Projectreaders;

use Illuminate\Console\Command;
use PKeidel\Laradockctl\Env\EnvFile;

class LaravelReader extends ProjectReader {

    private $envFile;
    private $stackname;
    private $webserver;
    private $command;

    public function detect(): bool {
        if(file_exists($c = 'composer.json')) {
            $data = json_decode(file_get_contents($c));
            return property_exists(optional($data)->require ?? [], 'laravel/framework');
        }
        return false;
    }

    public function __construct(Command $command) {
        $this->command = $command;
        $this->envFile = new EnvFile(base_path("laradock/.env"));
        $this->stackname = $this->askForAndSetStackName();
        $this->webserver = $this->askForAndSetWebserver();
        $this->askForAndSetPhpVersion();
        $this->askForAndSetPhpInterpreter();
    }

    public function getStackName() {
        return $this->stackname;
    }

    public function getNeededServies($containers = []) {
        $neededServices  = $containers;

        // main database
        $queueConnection = config('database.default');
        $queueDriver     = config("database.connections.$queueConnection.driver");
        if(in_array($queueDriver, ['mysql', 'pgsql', 'sqlsrv']))
            $neededServices[$queueDriver][] = 'maindatabase';

        // broadcasting
        if(strtolower(config('broadcasting.default')) === 'redis')
            $neededServices['redis'][] = 'broadcasting';

        // cache
        if(in_array($driver = strtolower(config('cache.default')), ["memcached", "redis", "apc"]))
            $neededServices[$driver][] = 'cache';

        // session
        if(in_array($driver = strtolower(config('session.driver')), ["memcached", "redis", "database", "dynamodb"])) {
            if($driver === 'database') {
                // get driver from connection
                $dbConnection = config('session.connection');
                $driver     = config("database.connections.$dbConnection.driver");
            }
            $neededServices[$driver][] = 'session';
        }

        // queue
        $queueConnection = config('queue.default');
        $queueDriver     = config("queue.connections.$queueConnection.driver");
        if(in_array($queueDriver, ['database', 'beanstalkd', 'redis']))
            $neededServices[$queueDriver][] = 'queue';

        $neededServices[$this->webserver] = ['webserver'];

        // check if commands do exist, then a scheduler is maybe needed
        if((file_exists($path = base_path('app/Console/Commands')) && count(scandir($path)) > 2) || $this->command->confirm('Do you want to active the scheduler (container: php-worker)?')) {
            if(file_exists($path = base_path('laradock/php-worker/supervisord.d/laravel-scheduler.conf.example')))
                rename($path, base_path('laradock/php-worker/supervisord.d/laravel-scheduler.conf'));
            $neededServices['php-worker'][] = 'scheduler';
        }

        // check if jobs do exist, then a queue is maybe needed
        if((file_exists($path = base_path('app/Jobs')) && count(scandir($path)) > 2) || $this->command->confirm('Do you want to active the queue workers (container: php-worker)?')) {
            if(file_exists($path = base_path('laradock/php-worker/supervisord.d/laravel-worker.conf.example'))) {
                rename($path, base_path('laradock/php-worker/supervisord.d/laravel-worker.conf'));
            }
            $neededServices['php-worker'][] = 'queue';
        }

        return $neededServices;
    }

    private function askForAndSetStackName() {
        // docker stack name
        $currentStackName  = $this->envFile->readKey('COMPOSE_PROJECT_NAME');
        $stackName         = $currentStackName;
        $stackNameRegex    = "/^([-a-z0-9]+)$/";
        while(true) {
            $stackName = $this->command->askWithCompletion("How should your docker stack be named? RegEx: $stackNameRegex", [$currentStackName], $currentStackName);
            preg_match($stackNameRegex, $stackName, $matches);
            if(count($matches) === 2)
                break;
            $this->warn('Name must match the Regex!');
        }
        $this->envFile->replaceOrAdd('COMPOSE_PROJECT_NAME', $stackName);
        return $stackName;
    }

    private function askForAndSetWebserver() {
        $envFile           = new EnvFile(base_path(".env"));
        $allowedWebservers = ['nginx', 'apache2', 'caddy'];
        $infos             = $envFile->readKey('LARADOCK_CONTAINERS');
        $infos             = explode(' ', $infos);
        $currentWebserver  = count($infos) ? (in_array($infos[0], $allowedWebservers) ? $infos[0] : $allowedWebservers[0]) : NULL;
        while(!in_array($webserver = $this->command->askWithCompletion("What webserver do you want to use?", $allowedWebservers, $currentWebserver), $allowedWebservers)) {
            // no op
        }
        $envFile->replaceOrAdd('LARADOCK_CONTAINERS', $webserver);
        return $webserver;
    }

    private function askForAndSetPhpVersion() {
        // accourding to laradock/.env PHP_VERSION
        $allowedPhpVersions = ['5.6', '7.0', '7.1', '7.2', '7.3', '7.4'];
        $currentPhpVersion  = $this->envFile->readKey('PHP_VERSION');
        while(!in_array($phpVersion = $this->command->askWithCompletion("What PHP version do you want to use?", $allowedPhpVersions, $currentPhpVersion), $allowedPhpVersions)) {
            // no op
        }
        $this->envFile->replaceOrAdd('PHP_VERSION', $phpVersion);
    }

    private function askForAndSetPhpInterpreter() {
        // accourding to laradock/.env PHP_VERSION
        $allowedInterpreters = ['hhvm', 'php-fpm'];
        $currentPhpInterpreter  = $this->envFile->readKey('PHP_INTERPRETER');
        while(!in_array($phpInterpreter = $this->command->askWithCompletion("What PHP interpreter do you want to use?", $allowedInterpreters, $currentPhpInterpreter), $allowedInterpreters)) {
            // no op
        }
        $this->envFile->replaceOrAdd('PHP_INTERPRETER', $phpInterpreter);
    }
}
