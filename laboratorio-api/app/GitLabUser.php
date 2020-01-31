<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GitLabUser extends Model {
    public $fillable = ['name', 'user','password', 'gitlab_code'];
    public $table = 'gitlab_users';
}
