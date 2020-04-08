<?php

namespace App\laboratorio\classroom;

use App\GitLabUser;
use App\laboratorio\gitlab\GitLab;
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

    public function addMembersByRegistrationCode(array $members_registration_code) {
        foreach($members_registration_code as $registration_code) {
            $user = GitLabUser::where('registration_number', $registration_code)->first();
            if(is_null($user)) {
                $users_not_found[] = $registration_code;
                continue;
            }

            $users_ids[] = $user->getId();
        }


    }

}
