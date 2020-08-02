<?php

namespace App\laboratorio\assignments;

use App\laboratorio\gitlab\GitUser;
use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;

class Assignment {
    public $name;
    public $description;
    public $classroom_external_id;
    public $assignment_external_id;
    public $import_from;
    public $parent_id;
    public $students;

    /**
     * Assignment constructor.
     * @param string $name
     * @param string|null $description
     * @param string $assignment_external_id
     * @param string|null $classroom_external_id
     * @param string|null $import_from
     * @param string|null $parent_id
     * @param AssignmentStudent[] $students
     */
    public function __construct(
        string $name,
        ?string $description,
        string $assignment_external_id,
        ?string $classroom_external_id,
        ?string $import_from,
        ?string $parent_id,
        array $students = null
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->classroom_external_id = $classroom_external_id;
        $this->assignment_external_id = $assignment_external_id;
        $this->import_from = $import_from;
        $this->parent_id = $parent_id;
        $this->students = $students;
    }

    public static function create(string $code, string $name, ?string $description, string $classroom_external_id, string $import_from) {
        $response = RemoteRepositoryResolver::resolve()->createGroup(
            $code,
            $name,
            self::buildDescription($description, $import_from),
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
            $parent_id              = $response->data->parent_id
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
            $parent_id              = $response->data->parent_id
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

        return new self(
            $name                   = $response->data->name,
            $description            = null,
            $assignment_external_id = $response->data->id,
            $classroom_external_id  = null, // todo
            $import_from            = $base_assignments_information->import_from,
            $parent_id              = $response->data->namespace->id
        );
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

    private static function buildDescription(?string $description, string $import_from) {
        return json_encode(['description' => $description, 'import_from' => $import_from]);
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

    public static function getAcceptedAssignmentsName(GitUser $user, Assignment $assignment): string {
        $accepted_assignment_name = "{$user->name}-{$assignment->name}";

        return $accepted_assignment_name;
    }

    public static function getAllStudents(string $code, string $assignment_external_id): Assignment {
        $response = RemoteRepositoryResolver::resolve()->getGroupDetails($code, $assignment_external_id);

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        $students = array_map(function($project) use ($code) {
            $parts = explode('-', $project->name);
            $student_user = $parts[0];
            $accepted_at = $project->created_at;
            $remote_url = $project->web_url;

            $response = RemoteRepositoryResolver::resolve()->getCommits($code, $project->id);
            $commits = array_map(function($commits) {
                return ["message" => $commits->message, "date" => $commits->committed_date];
            }, $response->data);

            return new AssignmentStudent($student_user, $accepted_at, $remote_url, $commits);
        }, $response->data->projects);

        return new self(
            $name = $response->data->name,
            $description = self::getDescription($response->data->description),
            $assignment_external_id = $response->data->id,
            $classroom_external_id = $response->data->parent_id,
            $import_from = self::getImportUrlFromDescription($response->data->description),
            $parent_id = $response->data->parent_id,
            $students
        );
    }

}
