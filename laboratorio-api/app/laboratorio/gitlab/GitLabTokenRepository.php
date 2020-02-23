<?php

namespace App\laboratorio\gitlab;

//use Illuminate\Support\Facades\Redis;

class GitLabTokenRepository {
//    private $token;

    public static function getToken(): string {
//        if(isset($this->token) && !$this->isExpired()) {
//            return $this->token;
//        }
//
//        return $this->token = $this->generateToken();

//        $token = Redis::get('gitlab_token');
        return "VDjCAhdpdycL2wMfPknj";
    }

    private function isExpired(): bool {
        //
    }

    private function generateToken(){
//        return GitLab::generateToken();
    }
}