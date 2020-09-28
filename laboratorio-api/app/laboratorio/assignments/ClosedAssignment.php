<?php

namespace App\laboratorio\assignments;

use Illuminate\Database\Eloquent\Model;

class ClosedAssignment extends Model {
    public $fillable = ['external_id', 'user_id', 'classroom_id', 'grade'];
    public $table = 'assignments';
}