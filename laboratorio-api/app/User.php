<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    public const TYPE_TEACHER = 'TEACHER';
    public const TYPE_STUDENT = 'STUDENT';

    public $fillable = ['id', 'name', 'username', 'registration_number', 'university_email', 'type', 'avatar_url', 'email'];
    public $table = 'users';
}
