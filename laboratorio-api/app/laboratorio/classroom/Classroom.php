<?php

namespace App\laboratorio\classroom;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;

class Classroom {
    public $name;
    public $description;
    public $members;

    public function create() {
        $response = RemoteRepositoryResolver::resolve()->createGroup($this->name, $this->description);
        if($response instanceof ErrorResponse) {
            throw new ClassroomException($response->data['error_message']);
        }
    }

}
