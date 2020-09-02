# README #

[![MIT License](https://poser.pugx.org/laravel/framework/license.svg)](https://packagist.org/packages/pkeidel/laradockctl)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/pkeidel/laradockctl.svg?style=flat-square)](https://packagist.org/packages/pkeidel/laradockctl)
[![Total Downloads](https://img.shields.io/packagist/dt/pkeidel/laradockctl.svg?style=flat-square)](https://packagist.org/packages/pkeidel/laradockctl)
<a href="https://packagist.org/packages/pkeidel/laradockctl"><img src="http://forthebadge.com/images/badges/makes-people-smile.svg" height="20px" /></a>

This package does the following things:
 * download laradock
 * detect which services are needed and configure them
 
At the moment is supports:
 * Laravel
 
How it works:

`ProjectReader` instances inspect your project and return what services are needed. Configs are adjusted to start these services via `php artisan laradock:up`.

With this package it is possible to auto-generate the needed configuration to use laradock.

It reads your laravel .env file and creates the needed docker containers. Then it starts the containers, reads the hostnames/ports/... and writes them to your .env file.

## Install

```shell
composer require pkeidel/laradock
```

### Usage
There are the following artisan commands:
* `laradock:install`     Installs laradock from github. If git is found it uses git else it downloads it as zip file
* `laradock:update`      Updates laradock. Either via git or via zip download
* `laradock:configure`   Enables the specified services and write the needed configuration to ./.env and laradock/.env
* `laradock:up`          Starts the specified containers from .env LARADOCK_CONTAINERS

There are also these commands but you may not need them for normal usage:
* `laradock:setenv`      Sets the value of a specified key in laradock/.env
