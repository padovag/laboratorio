<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model {
    public $fillable = ['name', 'username', 'registration_number', 'university_email'];
    public $table = 'users';
}
