<?php

namespace App\laboratorio\gitlab;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\TokenException;
use App\laboratorio\util\http\ErrorResponse;
use Illuminate\Support\Facades\Redis;

class GitLabTokenRepository {
    public static function getToken(string $code) {
        $token = Redis::get($code);
        if(!is_null($token)) {
            return $token;
        }

        self::retrieveAndSaveToken($code);

        return self::getToken($code);
    }

    public static function retrieveAndSaveToken(string $code): void {
        $token_response = RemoteRepositoryResolver::resolve()->getUserToken($code);
        if($token_response instanceof ErrorResponse) {
            throw new TokenException("Could not retrieve token from code");
        }

        $token = $token_response->data->access_token;
        Redis::set($code, $token);
    }
}
