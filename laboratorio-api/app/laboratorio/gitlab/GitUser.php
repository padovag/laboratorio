<?php

namespace App\laboratorio\gitlab;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;
use Exception;

class GitUser {
    public $name;
    public $username;

    public function __construct(string $name = null, string $username = null) {
        $this->name = $name;
        $this->username = $username;
    }

    public function getFromProvider(string $user_token): ?GitUser {
        $response = RemoteRepositoryResolver::resolve()->getUserByAccessToken($user_token);
        if($response instanceof ErrorResponse) {
            return null;
        }

        return new self($response->data->name, $response->data->username);
    }
}