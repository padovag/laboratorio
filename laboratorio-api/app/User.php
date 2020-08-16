<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    public const TYPE_TEACHER = 'TEACHER';
    public const TYPE_STUDENT = 'STUDENT';

    public $fillable = ['name', 'username', 'registration_number', 'university_email', 'type'];
    public $table = 'users';
}
