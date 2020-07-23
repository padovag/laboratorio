<?php

namespace App\laboratorio\assignments;

class AssignmentStudent {
    public $student_user;
    public $accepted_at;
    public $remote_url;

    public function __construct($student_user, $accepted_at, $remote_url) {
        $this->student_user = $student_user;
        $this->accepted_at = $accepted_at;
        $this->remote_url = $remote_url;
    }


}