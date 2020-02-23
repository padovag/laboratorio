<?php

namespace App\laboratorio\util\http;

class ErrorResponse extends Response {

    public function __construct(string $error) {
        parent::__construct("error", ['error_message' => $error]);
    }
}