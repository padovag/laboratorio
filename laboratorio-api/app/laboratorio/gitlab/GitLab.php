<?php

namespace App\laboratorio\gitlab;

use App\laboratorio\util\http\ErrorResponse;
use App\laboratorio\util\http\Response;
use App\laboratorio\util\http\SuccessResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class GitLab {
    private const GITLAB_URI = "https://gitlab.com/api/v4/";

    public function createGroup(string $name, string $description): Response {
        $query_parameters = ['name' => $name, 'path' => $name . '-group', 'description' => $description];
        $response = $this->makeRequest($resource = 'groups', $query_parameters);

        return $response;
    }

    public function addMembersToGroup(array $members_ids, string $group_id): Response {
        foreach($members_ids as $member_id) {
            $response = $this->addMemberToGroup($member_id, $group_id);
        }

        return $response;
    }

    public function addMemberToGroup(string $member_id, string $group_id, int $access_level = 30): Response {
        $response = $this->makeRequest(
            $resource = "groups/{$group_id}/members",
            ['user_id' => $member_id, 'access_level' => $access_level]
        );

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