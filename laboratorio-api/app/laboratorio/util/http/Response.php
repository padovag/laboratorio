<?php

namespace App\laboratorio\util\http;

class Response {
    public $status;
    public $data;

    public function __construct(string $status, $data) {
        $this->status = $status;
        $this->data = $data;
    }
}