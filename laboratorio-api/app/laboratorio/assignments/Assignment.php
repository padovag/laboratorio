<?php

namespace App\laboratorio\assignments;

use App\laboratorio\RemoteRepositoryResolver;
use App\laboratorio\util\http\ErrorResponse;

class Assignment {
    public $name;
    public $description;
    public $classroom_external_id;

    public function __construct(string $name, string $description, string $classroom_external_id) {
        $this->name = $name;
        $this->description = $description;
        $this->classroom_external_id = $classroom_external_id;
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

        return new self($response->data->name, $response->data->description, $response->data->id);
    }

    private static function buildDescription(?string $description, string $import_from) {
        return json_encode(['description' => $description, 'import_from' => $import_from]);
    }

}
