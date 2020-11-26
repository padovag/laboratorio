<?php

namespace App\laboratorio\assignments;

use Illuminate\Database\Eloquent\Model;

class ClosedAssignment extends Model {
    public $fillable = ['external_id', 'user_id', 'classroom_id', 'grade'];
    public $table = 'assignments';

    public static function getByStudent(string $assignment_id, string $student_user_id) {
        return ClosedAssignment::where(['external_id' => $assignment_id, 'user_id' => $student_user_id])->first();
    }
}
