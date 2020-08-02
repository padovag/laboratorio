<?php

namespace App\laboratorio\gitlab;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;

class GitUser {
    public $name;
    public $username;

    public function __construct(string $name = null, string $username = null) {
        $this->name = $name;
        $this->username = $username;
    }

    public function getFromProvider(string $code): ?GitUser {
        $token_response = RemoteRepositoryResolver::resolve()->getUserToken($code);
        if($token_response instanceof ErrorResponse) {
            return null;
        }

        $token = $token_response->data->access_token;
        $response = RemoteRepositoryResolver::resolve()->getUserByAccessToken($token);
        if($response instanceof ErrorResponse) {
            return null;
        }

        return new self($response->data->name, $response->data->username);
    }
}