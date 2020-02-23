<?php

namespace Tests\laboratorio\gitlab;

use App\laboratorio\gitlab\GitLab;
use App\laboratorio\util\http\ErrorResponse;
use App\laboratorio\util\http\Response;
use App\laboratorio\util\http\SuccessResponse;

class GitLabForTests extends GitLab {
    private $mocked_response;

    public function mockSuccessResponse($data) {
        $this->mocked_response = new SuccessResponse($data);
    }

    public function mockErrorResponse(string $error) {
        $this->mocked_response = new ErrorResponse($error);
    }

    protected function makeRequest(string $resource, array $query_parameters, string $method = 'POST'): Response {
        return $this->mocked_response ?? new ErrorResponse("You have to mock a response");
    }
}