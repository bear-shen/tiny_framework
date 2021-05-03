<?php namespace Lib;


class FilePath {
    private $hash     = '';
    private $hashDir  = '';
    private $hashPath = '';

    public static function load($hash) {
        $self           = new self();
        $self->hash     = $hash;
        $self->hashDir  = substr($hash, 0, 2) . DIRECTORY_SEPARATOR .
                          substr($hash, 2, 4);
        $self->hashPath = $self->hashDir . DIRECTORY_SEPARATOR .
                          substr($hash, 4);
        return $self;
    }

    public function localDir() {
        return FILE_ROOT . DIRECTORY_SEPARATOR . $this->hashDir;
    }

    public function local() {
        return FILE_ROOT . DIRECTORY_SEPARATOR . $this->hashPath;
    }

    public function webDir() {
        return FILE_URL . DIRECTORY_SEPARATOR . $this->hashDir;
    }

    public function web() {
        return FILE_URL . DIRECTORY_SEPARATOR . $this->hashPath;
    }
}