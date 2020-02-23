<?php

namespace App\laboratorio;

use App\laboratorio\gitlab\GitLab;

class RemoteRepositoryResolver {
    private static $remote_repository;

    public static function resolve() {
        return self::$remote_repository ?? self::$remote_repository = new GitLab();
    }
}