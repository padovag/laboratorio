<?php

namespace App\laboratorio\gitlab;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;

class GitUser {
    public $name;
    public $username;
    public $avatar_url;
    public $email;

    public function __construct(string $name = null, string $username = null, string $avatar_url = null, string $email = null) {
        $this->name = $name;
        $this->username = $username;
        $this->avatar_url = $avatar_url;
        $this->email = $email;
    }

    public function getFromProvider(string $code): ?GitUser {
        $token = GitLabTokenRepository::getToken($code);
        $response = RemoteRepositoryResolver::resolve()->getUserByAccessToken($token);

        if($response instanceof ErrorResponse) {
            return null;
        }

        return new self($response->data->name, $response->data->username, $response->data->avatar_url, $response->data->public_email);
    }
}