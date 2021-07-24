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
        $this->askForAndSetPhpInterpreter();
        $this->askForAndSetPhpVersion();
    }

    public function getStackName() {
        return $this->stackname;
    }

    public function getNeededServies($containers = []) {
        $neededServices  = $containers;

        // main database
        $dbConnection = config('database.default');
        $dbDriver     = config("database.connections.$dbConnection.driver");
        if(in_array($dbDriver, ['mysql', 'pgsql', 'sqlsrv']))
            $neededServices[$dbDriver][] = 'maindatabase';

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
        if(in_array($queueDriver, ['beanstalkd', 'redis']))
            $neededServices[$queueDriver][] = 'queue';
        elseif($queueDriver === 'database')
            $neededServices[$dbDriver][] = 'queue';

        $neededServices[$this->webserver] = ['webserver'];

        // check if commands do exist, then a scheduler is maybe needed
        $autoenable = (file_exists($path = base_path('app/Console/Commands')) && count(scandir($path)) > 2);
        if(
            $autoenable || $this->command->confirm('Do you want to activate the scheduler (container: php-worker)?')
        ) {
            if($autoenable)
                $this->command->warn('console commands found, scheduler service is activated automaticly');
            if(file_exists($path = base_path('laradock/php-worker/supervisord.d/laravel-scheduler.conf.example')))
                rename($path, base_path('laradock/php-worker/supervisord.d/laravel-scheduler.conf'));
            $neededServices['php-worker'][] = 'scheduler';
        }

        // check if jobs do exist, then a queue is maybe needed
        if((file_exists($path = base_path('app/Jobs')) && count(scandir($path)) > 2) || $this->command->confirm('Do you want to activate the queue workers (container: php-worker)?')) {
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
            $this->command->warn('Name must match the Regex!');
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
        while(!in_array($webserver = $this->command->askWithCompletion("What webserver do you want to use?", $allowedWebservers, $currentWebserver), $allowedWebservers));
        $envFile->replaceOrAdd('LARADOCK_CONTAINERS', $webserver);
        return $webserver;
    }

    private function askForAndSetPhpVersion() {

        // if user selected hhvm, don't ask him for php version
        if($this->envFile->readKey('PHP_INTERPRETER') !== 'php-fpm') {
            return;
        }

        // accourding to laradock/.env PHP_VERSION
        $allowedPhpVersions = ['7.1', '7.2', '7.3', '7.4', '8.0'];

        try {
            $json = json_decode(file_get_contents('https://hub.docker.com/v2/repositories/laradock/php-fpm/tags/?page_size=100&page=1&ordering=last_updated'), true, 512, JSON_THROW_ON_ERROR);
            $allowedPhpVersions = [];
            foreach($json['results'] as $result) {
                if(substr($result['name'], 0, 7) === 'latest-') {
                    $allowedPhpVersions[] = substr($result['name'], 7);
                }
            }
            unset($json);
            sort($allowedPhpVersions);
//            $this->command->info('Allowed: ' . implode(', ', $allowedPhpVersions));
        } catch (\Throwable $t) {
            // nothing, just keep the default versions
        }

        $currentPhpVersion  = $this->envFile->readKey('PHP_VERSION') ?? last($allowedPhpVersions);
        while(!in_array($phpVersion = $this->command->askWithCompletion("What PHP version do you want to use?", $allowedPhpVersions, $currentPhpVersion), $allowedPhpVersions));
        $this->envFile->replaceOrAdd('PHP_VERSION', $phpVersion);
    }

    private function askForAndSetPhpInterpreter() {
        // accourding to laradock/.env PHP_VERSION
        $allowedInterpreters = ['hhvm', 'php-fpm'];
        $currentPhpInterpreter  = $this->envFile->readKey('PHP_INTERPRETER');
        while(!in_array($phpInterpreter = $this->command->askWithCompletion("What PHP interpreter do you want to use?", $allowedInterpreters, $currentPhpInterpreter), $allowedInterpreters));
        $this->envFile->replaceOrAdd('PHP_INTERPRETER', $phpInterpreter);
    }
}
