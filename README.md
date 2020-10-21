# README #

[![MIT License](https://poser.pugx.org/laravel/framework/license.svg)](https://packagist.org/packages/pkeidel/laradockctl)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/pkeidel/laradockctl.svg?style=flat-square)](https://packagist.org/packages/pkeidel/laradockctl)
[![Total Downloads](https://img.shields.io/packagist/dt/pkeidel/laradockctl.svg?style=flat-square)](https://packagist.org/packages/pkeidel/laradockctl)
<a href="https://packagist.org/packages/pkeidel/laradockctl"><img src="http://forthebadge.com/images/badges/makes-people-smile.svg" height="20px" /></a>

This package does the following things:
 * download laradock
 * detect which services are needed and configure them
 
At the moment it supports:
 * Laravel

For laravel it also does:
 * configure your database connection
 * configure your redis connection
 
How it works:

`ProjectReader` instances inspect your project and return what services are needed. Configs are adjusted to start these services via `php artisan laradock:up`.

## Install

```shell
composer require pkeidel/laradockctl --dev
```

### Usage
* `laradock:install`
* `laradock:configure`
* `laradock:up`


### Commands
There are the following artisan commands:
* `laradock:install`     Installs laradock from github. If git is found it uses git else it downloads it as zip file
* `laradock:configure`   Enables the specified services and write the needed configuration to ./.env and laradock/.env
* `laradock:up`          Starts all your containers
  * `laradock:up --only="php-fpm nginx"`
* `laradock:exec`        Executes 'bash' in 'php-fpm' container
  * `laradock:exec nginx` Executes 'bash' in 'nginx' container
  * `laradock:exec nginx --cmd=sh` Executes 'sh' in 'nginx' container
* `laradock:logs`        tail -f log output for all your containers
* `laradock:update`      Updates laradock. Either via git or via zip download

There are also these commands but you may not need them for normal usage:
* `laradock:setenv`      Sets the value of a specified key in laradock/.env
