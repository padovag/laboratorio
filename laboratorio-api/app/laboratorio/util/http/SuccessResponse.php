<?php

namespace App\laboratorio\util\http;

class SuccessResponse extends Response {
    public function __construct($data) {
        parent::__construct("success", $data);
    }
}