<?php

namespace PKeidel\Laradockctl\Commands;

use Illuminate\Console\Command;
use PKeidel\Laradockctl\Env\EnvFile;
use PKeidel\Laradockctl\Projectreaders\ProjectReader;
use PKeidel\Laradockctl\Projectwriters\ProjectWriter;
use PKeidel\Laradockctl\Yaml\Yaml;

class LaradockConfigureCommand extends Command {
    use GitZipHelper;

    protected $signature = 'laradock:configure';
    protected $description = 'Enables the specified services and write the needed configuration to ./.env and laradock/.env';

    private $stackName;
    private $laradockEnvFile;
    private $envFileProject;
    private $yaml;
    private $containers;

    public function handle() {
        if(!$this->isInstalled()) {
            $this->warn("Laradock is not installed yet. Please execute laradock:install");
            exit;
        }

        // read configs, create readers and writers for docker-compose.yml, laradock/.env, .env
        // detect which containers are needed
        $this->init();
        $this->editLaradockEnvConfig();

        // clean up
        if(in_array('apc', $this->containers))
            unset($this->containers[array_search('apc', $this->containers)]);

        $this->envFileProject->replaceOrAdd('LARADOCK_CONTAINERS', $containersStr = implode(' ', $this->containers));

        // remove not needed volumes
        $removeVolumes = array_diff($this->yaml->getKeys('volumes'), array_merge($this->containers, ['elasticsearch']));
        foreach($removeVolumes as $vol)
            $this->yaml->unset("volumes.$vol");

        // check for any LARADOCK_REPLACE_IMAGE_* env variables
        $this->warn('You can replace/override the container image to use your own ones');
        $this->info('  Example:');
        $this->info('    Add to .env: LARADOCK_REPLACE_IMAGE_PHP_FPM="example.com/myown/php-fpm:7-latest"');
        $this->info('');
        $this->warn('This are the possible keys you can replace:');
        foreach($this->containers as $container) {
            $c = strtoupper(str_replace(['-'], '_', $container));
            $this->info("  LARADOCK_REPLACE_IMAGE_$c");
        }
        $this->info('');

        if (!$this->confirm('Make the changes in your .env and then hit ENTER to resume the configurtion', true)) {
            $this->error('Exiting. Restart the command to complete your configuration.');
            return;
        }

        foreach($this->containers as $container) {
            $c = strtoupper(str_replace(['-'], '_', $container));
            $this->info("Checking .env for LARADOCK_REPLACE_IMAGE_$c => " . ($val = $this->envFileProject->readKey("LARADOCK_REPLACE_IMAGE_$c")));
            if(!empty($val)) {
                // remove services.[$container].build
//                $this->info("unset services.$container.build");
                $this->yaml->unset("services.$container.build");
                // add services.[$container].image
                $this->info("  set services.$container.image => $val");
                $this->yaml->set("services.$container.image", $val);
            }
        }

        $this->yaml->save();
        $this->info('');

        $this->warn("Killing all containers");
        sleep(1);
        passthru("cd {$this->dirname} && docker-compose kill");

        $this->warn("Building containers: $containersStr");
        sleep(1);
        passthru("cd {$this->dirname} && docker-compose build $containersStr");

        $this->warn("Starting containers: $containersStr");
        sleep(1);
        passthru("cd {$this->dirname} && docker-compose up -d $containersStr");

        $dockerConfig = [];

        // create project database if not existing
        if(in_array('mysql', $this->containers)) {

            $c = $this->getContainerName('mysql');
            $i = $this->getDockerInspect($c);
            if(empty($i)) {
                die("Error calling docker inspect mysql. Container $c must be started!");
            }
            $rootPw = collect($i['env'])
                ->map(function($l) {
                    return explode('=', $l, 2);
                })
                ->filter(function($i) {
                    return count($i) === 2 && $i[0] === 'MYSQL_ROOT_PASSWORD';
                })
                ->map(function($i) {
                    return $i[1];
                })
                ->first();

            $dbname = $this->envFileProject->readKey('DB_DATABASE') ?: $this->stackName;
            $this->info("existing databases:");
            passthru("cd {$this->dirname} && echo \"show databases;\" | docker-compose exec -T mysql mysql -uroot -p$rootPw");
            $this->info("trying to create database: $dbname");
            passthru("cd {$this->dirname} && echo \"create database `$dbname`;\" | docker-compose exec -T mysql mysql -uroot -p$rootPw");

            $dockerConfig['DB_USERNAME'] = 'root';
            $dockerConfig['DB_PASSWORD'] = $rootPw;
            $dockerConfig['DB_HOST'] = 'mysql';
        }

        if(in_array('redis', $this->containers)) {
            $dockerConfig['REDIS_HOST'] = 'redis';
        }

        // Write docker config to file (for example .env in case of laravel)
        $projectWriter = ProjectWriter::getInstance();
        $projectWriter->writeConfigToProject($dockerConfig);

        $this->info("FINISHED!");
    }

    private function getContainerName($c) {
        return $this->stackName.'_'.$c.'_1';
    }

    private function getDockerInspect($c) {
        $inspect = json_decode(shell_exec("docker container inspect $c"), true);
        if(!count($inspect)) {
            return [];
        }
        $inspect = $inspect[0];
        return [
            'ports' => $inspect['NetworkSettings']['Ports'],
            'env' => $inspect['Config']['Env'],
        ];
    }

    private function init() {
        $projectReader    = ProjectReader::get($this);
        $services         = $projectReader->getNeededServies();
        $this->stackName  = $projectReader->getStackName();
        $this->containers = array_keys($services);

        $this->yaml = Yaml::parse(base_path('laradock/docker-compose.yml'));
        $this->laradockEnvFile = new EnvFile(base_path("laradock/.env"));
        $this->envFileProject  = new EnvFile(base_path(".env"));

        // also load depends_on containers
        $this->detectDependsOn();
    }

    /**
     * check containers for depends_on key and add these to $this->containers as well
     */
    private function detectDependsOn(): void {
        for ($i = 0; $i < count($this->containers); $i++) {
            $additional = $this->yaml->get("services." . $this->containers[$i] . ".depends_on");
            if (is_array($additional)) {
                foreach ($additional as $add) {
                    if (!in_array($add, $this->containers)) {
                        $this->info($this->containers[$i] . " depends_on: $add");
                        $this->containers[count($this->containers)] = $add;
                    }
                }
            }
        }
    }

    /**
     * read config and set some .env values
     */
    private function editLaradockEnvConfig(): void {
        $this->laradockEnvFile->replaceOrAdd('PHP_FPM_INSTALL_PHPREDIS', in_array('redis', $this->containers));
        $this->laradockEnvFile->replaceOrAdd('PHP_FPM_INSTALL_PGSQL', in_array('postgres', $this->containers));
        $this->laradockEnvFile->replaceOrAdd('PHP_FPM_INSTALL_APCU', in_array('apc', $this->containers));
    }
}
