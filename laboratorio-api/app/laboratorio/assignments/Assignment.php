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

    public function __construct(
        string $name,
        ?string $description,
        string $assignment_external_id,
        ?string $classroom_external_id,
        ?string $import_from,
        ?string $parent_id
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->classroom_external_id = $classroom_external_id;
        $this->assignment_external_id = $assignment_external_id;
        $this->import_from = $import_from;
        $this->parent_id = $parent_id;
    }

    public static function create(string $provider_access_token, string $name, ?string $description, string $classroom_external_id, string $import_from) {
        $response = RemoteRepositoryResolver::resolve()->createGroup(
            $provider_access_token,
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

    public static function get(string $provider_access_token, string $assignment_external_id) {
        $response = RemoteRepositoryResolver::resolve()->getGroupDetails($provider_access_token, $assignment_external_id);
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

    public static function accept(string $provider_access_token, string $assignment_external_id) {
        $accepted_assignment_name = self::getAcceptedAssignmentsName($provider_access_token, $assignment_external_id);
        $response = RemoteRepositoryResolver::resolve()->createProject($provider_access_token, $assignment_external_id, $accepted_assignment_name);

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        return new self(
            $name                   = $response->data->name,
            $description            = null,
            $assignment_external_id = $response->data->id,
            $classroom_external_id  = null, // todo
            $import_from            = null,
            $parent_id              = $response->data->namespace->id
        );
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

    public static function getAcceptedAssignmentsName(string $provider_access_token, string $assignment_external_id): string {
        $base_assignments_information = self::get($provider_access_token, $assignment_external_id);
        $user = (new GitUser())->getFromProvider($provider_access_token);

        if(is_null($user) || is_null($base_assignments_information)) {
            throw new AssignmentException("User or base assignment could not be found");
        }

        $accepted_assignment_name = "{$user->name}-{$base_assignments_information->name}";

        return $accepted_assignment_name;
    }

}
