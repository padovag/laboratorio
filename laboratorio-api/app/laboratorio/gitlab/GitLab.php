<?php

namespace App\laboratorio\gitlab;

use App\laboratorio\util\http\ErrorResponse;
use App\laboratorio\util\http\Response;
use App\laboratorio\util\http\SuccessResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class GitLab {
    private const GITLAB_URI = "https://gitlab.com/";
    private const GITLAB_API_URI = self::GITLAB_URI . "api/v4/";

    public function getUserByAccessToken(string $token) {
        try {
            $user_id = $this->getUserIdByAccessToken($token);
            $user_response = $this->getUserById($user_id);

            return $user_response;
        } catch(GitLabException $exception) {
            return new ErrorResponse($exception->getMessage());
        }
    }

    public function getAccessTokenInfo(string $token) {
        $query_parameters = ['access_token' => $token];
        $response = $this->makeRequest(self::GITLAB_URI, $resource = 'oauth/token/info', $query_parameters, 'GET');

        return $response;
    }

    public function getUserById(string $user_id) {
        $query_parameters = ['id' => $user_id];
        $response = $this->makeRequest(self::GITLAB_API_URI, $resource = 'user', $query_parameters, 'GET');

        return $response;
    }

    public function createGroup(string $name, string $description): Response {
        $query_parameters = ['name' => $name, 'path' => $name . '-group', 'description' => $description];
        $response = $this->makeRequest(self::GITLAB_API_URI, $resource = 'groups', $query_parameters);

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
            self::GITLAB_API_URI,
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

    protected function makeRequest(string $uri, string $resource, array $query_parameters, string $method = 'POST'): Response {
        try {
            $client = new Client();
            $response = $client->request(
                $method,
                $uri . $resource,
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

    private function getUserIdByAccessToken(string $token): ?string {
        $token_info = $this->getAccessTokenInfo($token);

        if($token_info instanceof ErrorResponse) {
            throw new GitLabException($token_info->data['error_message']);
        }

        return  $token_info->data->resource_owner_id;
    }
}