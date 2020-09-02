<?php

namespace PKeidel\Laradockctl\Yaml;

use Illuminate\Support\Arr;

/**
 * Class Yaml
 * <code>
 * $yaml = Yaml::parse(base_path('laradock/docker-compose.yml'));
 * dump($yaml->asArray());
 * $yaml->set('')
 * $yaml->save()
 * </code>
 * @package PKeidel\Laradockctl\Yaml
 */
class Yaml {

    private $path;
    private $data;

    /**
     * Loads a .y(a)ml file
     * @param string $path
     * @return static
     */
    public static function parse($path) {
        $yaml = new static();
        $yaml->path = $path;
        $yaml->data = yaml_parse_file($path);
        return $yaml;
    }

    /**
     * Returns the content of the current yaml as an array
     * @return mixed
     */
    public function asArray() {
        return $this->data;
    }

    /**
     * Sets a key/value. Key supports "dot" notation
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value) {
        Arr::set($this->data, $key, $value);
        return $this;
    }

    public function unset($key) {
        Arr::forget($this->data, $key);
        return $this;
    }

    public function save($path = NULL) {
        yaml_emit_file($path ?? $this->path, $this->data);
        return $this;
    }

    public function get(string $key) {
        return Arr::get($this->data, $key);
    }

    public function getKeys(string $key) {
        if(($data = Arr::get($this->data, $key)) === NULL)
            return NULL;
        return array_keys($data);
    }
}
