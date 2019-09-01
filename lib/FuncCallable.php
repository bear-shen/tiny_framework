<?php namespace Lib;


trait FuncCallable {
    public static function __callStatic($name, $arguments) {
        $name = '_' . $name;
        return (new self)->$name(...$arguments);
    }

    public function __call($name, $arguments) {
        $name = '_' . $name;
        return $this->$name(...$arguments);
    }
}