<?php

namespace App\laboratorio\assignments;

use App\laboratorio\gitlab\GitUser;
use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;
use App\User;

class Assignment {
    public const CLOSED_STATUS = 'CLOSED';
    public const OPENED_STATUS = 'OPENED';

    public $name;
    public $description;
    public $classroom_external_id;
    public $assignment_external_id;
    public $import_from;
    public $parent_id;
    public $students;
    public $due_date;
    public $status;
    public $visibility;
    public $remote_url;

    /**
     * Assignment constructor.
     * @param string $name
     * @param string|null $description
     * @param string $assignment_external_id
     * @param string|null $classroom_external_id
     * @param string|null $import_from
     * @param string|null $parent_id
     * @param string $due_date
     * @param string $status
     * @param string|null $visibility
     * @param AssignmentStudent[] $students
     */
    public function __construct(
        string $name,
        ?string $description,
        string $assignment_external_id,
        ?string $classroom_external_id,
        ?string $import_from,
        ?string $parent_id,
        string $due_date,
        string $status,
        ?string $visibility,
        string $remote_url,
        array $students = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->classroom_external_id = $classroom_external_id;
        $this->assignment_external_id = $assignment_external_id;
        $this->import_from = $import_from;
        $this->parent_id = $parent_id;
        $this->students = $students;
        $this->due_date = $due_date;
        $this->status = $status;
        $this->visibility = $visibility ?? 'private';
        $this->remote_url = $remote_url;
    }

    public static function create(
        string $code,
        string $name,
        ?string $description,
        string $classroom_external_id,
        ?string $import_from,
        string $due_date,
        ?string $visibility
    ) {
        $response = RemoteRepositoryResolver::resolve()->createGroup(
            $code,
            $name,
            self::buildDescription($description, $import_from, $due_date),
            $visibility ?? 'private',
            $classroom_external_id
        );

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        return new self(
            $name                   = $response->data->name,
            $description            = self::getDescription($response->data->description),
            $assignment_external_id = $response->data->id,
            $classroom_external_id  = $response->data->parent_id,
            $import_from            = self::getImportUrlFromDescription($response->data->description),
            $parent_id              = $response->data->parent_id,
            $due_date               = self::getDueDateFromDescription($response->data->description),
            $status                 = self::getStatus($due_date),
            $visibility,
            $remote_url             = $response->data->web_url
        );
    }

    public static function get(string $code, string $assignment_external_id) {
        $response = RemoteRepositoryResolver::resolve()->getGroupDetails($code, $assignment_external_id);
        // to do get child assignments

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        return new self(
            $name                   = $response->data->name,
            $description            = self::getDescription($response->data->description),
            $assignment_external_id = $response->data->id,
            $classroom_external_id  = $response->data->parent_id,
            $import_from            = self::getImportUrlFromDescription($response->data->description),
            $parent_id              = $response->data->parent_id,
            $due_date               = self::getDueDateFromDescription($response->data->description),
            $status                 = self::getStatus($due_date, $assignment_external_id),
            $visibility             = $response->data->visibility,
            $remote_url             = $response->data->web_url
        );
    }

    public static function accept(string $code, string $assignment_external_id) {
        $user = (new GitUser())->getFromProvider($code);
        if(is_null($user)) {
            throw new AssignmentException("User could not be found");
        }

        $base_assignments_information = self::get($code, $assignment_external_id);
        if(is_null($base_assignments_information)) {
            throw new AssignmentException("Base assignment could not be found");
        }

        $response = RemoteRepositoryResolver::resolve()->createProject(
            $code,
            $assignment_external_id,
            self::getAcceptedAssignmentsName($user, $base_assignments_information),
            $base_assignments_information->import_from
        );

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        RemoteRepositoryResolver::resolve()->starProject($code, $response->data->id);

        return new self(
            $name                   = $response->data->name,
            $description            = $base_assignments_information->description,
            $assignment_external_id = $response->data->id,
            $classroom_external_id  = null, // todo
            $import_from            = $base_assignments_information->import_from,
            $parent_id              = $response->data->namespace->id,
            $due_date               = $base_assignments_information->due_date,
            $status                 = $base_assignments_information->status,
            $visibility             = $base_assignments_information->visibility,
            $remote_url             = $response->data->web_url
        );
    }

    public static function isAccepted(string $code, string $assignment_external_id): bool {
        $response = RemoteRepositoryResolver::resolve()->getProjectsByUser($code, $assignment_external_id);

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        return $is_accepted = !empty($response->data);
    }

    public static function delete(string $code, string $assignment_external_id) {
        $response = RemoteRepositoryResolver::resolve()->deleteGroup($code, $assignment_external_id);

        if($response instanceof ErrorResponse) {
            $response = RemoteRepositoryResolver::resolve()->deleteProject($code, $assignment_external_id);
            if($response instanceof ErrorResponse) {
                throw new AssignmentException($response->data['error_message']);
            }
        }

        return $assignment_external_id;
    }

    public static function getStudents(string $code, string $assignment_external_id, string $assignment_status = null) {
        $assignment_students = self::getAllStudents($code, $assignment_external_id);
        if(isset($assignment_status)) {
            $students_with_status = array_filter($assignment_students->students, function($student) {
                return !empty($student->contributions);
            });
            $assignment_students->students = $students_with_status;
        }
        return $assignment_students;
    }

    private static function buildDescription(?string $description, ?string $import_from, string $due_date) {
        return json_encode(['description' => $description, 'import_from' => $import_from, 'due_date' => $due_date]);
    }

    private static function getDueDateFromDescription(string $description): ?string {
        return self::tearDownDescription($description)->due_date;
    }

    private static function getImportUrlFromDescription(string $description): ?string {
        return self::tearDownDescription($description)->import_from;
    }

    private static function getDescription(string $description): ?string {
        return self::tearDownDescription($description)->description;
    }

    private static function tearDownDescription(string $description): \stdClass {
        return json_decode($description);
    }

    private static function getStatus(string $due_date, string $assignment_external_id = null) {
        $closed_assignment = ClosedAssignment::where('external_id', $assignment_external_id)->first();
        if ($closed_assignment) {
            return self::CLOSED_STATUS;
        }

        $due_date = new \DateTime($due_date);
        $now = new \DateTime();

        if($due_date < $now) {
            return self::CLOSED_STATUS;
        }

        return self::OPENED_STATUS;
    }

    public static function getAcceptedAssignmentsName(GitUser $user, Assignment $assignment): string {
        $accepted_assignment_name = "{$user->name}-{$assignment->name}";

        return $accepted_assignment_name;
    }

    public static function getAllStudents(string $code, string $assignment_external_id): Assignment {
        $response = RemoteRepositoryResolver::resolve()->getGroupDetails($code, $assignment_external_id);

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        $students = array_map(function($project) use ($code, $assignment_external_id) {
            $parts = explode('-', $project->name);
            $student_user = $parts[0];
            $accepted_at = $project->created_at;
            $remote_url = $project->web_url;

            $response = RemoteRepositoryResolver::resolve()->getCommits($code, $project->id);
            $commits = array_map(function($commits) {
                return ["message" => $commits->message, "date" => $commits->committed_date, 'commit_url' => $commits->web_url];
            }, $response->data);
            $user = User::where('name', $student_user)->first();
            $closed_assignment = ClosedAssignment::getByStudent($assignment_external_id, $user->id);
            $grade = !is_null($closed_assignment) ? $closed_assignment->grade : null;

            return new AssignmentStudent($student_user, $accepted_at, $remote_url, $commits, $grade);
        }, $response->data->projects);

        return new self(
            $name = $response->data->name,
            $description = self::getDescription($response->data->description),
            $assignment_external_id = $response->data->id,
            $classroom_external_id = $response->data->parent_id,
            $import_from = self::getImportUrlFromDescription($response->data->description),
            $parent_id = $response->data->parent_id,
            $due_date = self::getDueDateFromDescription($response->data->description),
            $status = self::getStatus($due_date),
            $visibility = $response->data->visibility,
            $remote_url = $response->data->web_url,
            $students
        );
    }

    public static function list(string $classroom_external_id, ?string $filter_by, string $code) {
        $response = RemoteRepositoryResolver::resolve()->getSubgroups($code, $classroom_external_id);

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        $assignments = array_map(function($subgroup) {
            return new self(
                $name = $subgroup->name,
                $description = self::getDescription($subgroup->description),
                $assignment_external_id = $subgroup->id,
                $classroom_external_id = $subgroup->parent_id,
                $import_from = self::getImportUrlFromDescription($subgroup->description),
                $parent_id = $subgroup->parent_id,
                $due_date = self::getDueDateFromDescription($subgroup->description),
                $status = self::getStatus($due_date, $assignment_external_id),
                $visibility = $subgroup->visibility,
                $remote_url = $subgroup->web_url

            );
        }, $response->data);

        if(!is_null($filter_by)){
           $assignments = array_filter($assignments, function(Assignment $assignment) use ($filter_by){
               return $assignment->status == $filter_by;
           });
        }

        return $assignments;
    }

    public static function getReadMe(string $repository_url) {
        $response = RemoteRepositoryResolver::resolve()->getReadMe($repository_url);

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        return $response->data;
    }

}
