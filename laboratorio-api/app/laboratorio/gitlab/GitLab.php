<?php

namespace App\laboratorio\gitlab;

use App\laboratorio\util\http\ErrorResponse;
use App\laboratorio\util\http\Response;
use App\laboratorio\util\http\SuccessResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class GitLab {
    private const GITLAB_URI = "https://gitlab.com/api/v4/";

    public function createGroup(string $name, string $description) {
        $query_parameters = ['name' => $name, 'path' => $name . '-group', 'description' => $description];
        $response = $this->makeRequest($resource = 'groups', $query_parameters);

        return $response;
    }

    public static function getClientId() {
        return getenv("GITLAB_CLIENT_ID");
    }

    public static function getClientSecret() {
        return getenv("GITLAB_CLIENT_SECRET");
    }

    protected function makeRequest(string $resource, array $query_parameters, string $method = 'POST'): Response {
        try {
            $client = new Client();
            $response = $client->request(
                $method,
                self::GITLAB_URI . $resource,
                [
                    'headers' => self::getHeaders(),
                    'query' => $query_parameters
                ]
            );

            return new SuccessResponse(json_decode($response->getBody()->getContents()));
        } catch(ClientException $exception) {
            return new ErrorResponse($exception->getMessage());
        }
    }

    private static function getHeaders(): array {
        return [
            'PRIVATE-TOKEN' => GitLabTokenRepository::getToken()
        ];
    }
}