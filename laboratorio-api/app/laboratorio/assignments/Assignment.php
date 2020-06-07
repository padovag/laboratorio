<?php

namespace App\laboratorio\assignments;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;

class Assignment {
    public $name;
    public $description;
    public $assignment_external_id;
    public $import_from;
    public $parent_id;

    public function __construct(
        string $name,
        ?string $description,
        string $classroom_external_id,
        ?string $import_from,
        ?string $parent_id
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->assignment_external_id = $classroom_external_id;
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
            $response->data->name,
            self::getDescription($response->data->description),
            $response->data->id,
            self::getImportUrlFromDescription($response->data->description),
            null
        );
    }

    public static function get(string $provider_access_token, string $assignment_external_id) {
        $response = RemoteRepositoryResolver::resolve()->getGroupDetails($provider_access_token, $assignment_external_id);

        if($response instanceof ErrorResponse) {
            throw new AssignmentException($response->data['error_message']);
        }

        return new self(
            $response->data->name,
            self::getDescription($response->data->description),
            $response->data->id,
            self::getImportUrlFromDescription($response->data->description),
            $response->data->parent_id
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

}
