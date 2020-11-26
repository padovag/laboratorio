<?php

namespace App\laboratorio\assignments;

class AssignmentStudent {
    public $student_user;
    public $accepted_at;
    public $remote_url;
    public $contributions;
    public $grade;

    public function __construct($student_user, $accepted_at, $remote_url, $contributions, $grade) {
        $this->student_user = $student_user;
        $this->accepted_at = $accepted_at;
        $this->remote_url = $remote_url;
        $this->contributions = $contributions;
        $this->grade = $grade;
    }


}
