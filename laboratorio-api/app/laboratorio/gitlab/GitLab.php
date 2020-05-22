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

    public function getUser(string $username) {
        $query_parameters = ['username' => $username];
        $response = $this->makeRequest(self::GITLAB_API_URI, $resource = 'users', $query_parameters, 'GET');

        return $response;
    }

    public function getUserById(string $user_id) {
        $query_parameters = ['id' => $user_id];
        $response = $this->makeRequest(self::GITLAB_API_URI, $resource = 'user', $query_parameters, 'GET');

        return $response;
    }

    public function createGroup(string $name, string $description, string $token): Response {
        $query_parameters = ['name' => $name, 'path' => $name . '-group', 'description' => $description];
        $response = $this->makeRequest(self::GITLAB_API_URI, $resource = 'groups', $query_parameters, 'POST', $token);

        return $response;
    }

    public function addMembersToGroup(array $members_ids, string $group_id, string $token): Response {
        foreach($members_ids as $member_id) {
            $response = $this->addMemberToGroup($member_id, $group_id, $token);
        }

        return $response;
    }

    public function addMemberToGroup(string $member_id, string $group_id, string $token, int $access_level = 30): Response {
        $response = $this->makeRequest(
            self::GITLAB_API_URI,
            $resource = "groups/{$group_id}/members",
            ['user_id' => $member_id, 'access_level' => $access_level],
            'POST',
            $token
        );

        return $response;
    }

    public static function getClientId() {
        return getenv("GITLAB_CLIENT_ID");
    }

    public static function getClientSecret() {
        return getenv("GITLAB_CLIENT_SECRET");
    }

    protected function makeRequest(string $uri, string $resource, array $query_parameters, string $method = 'POST', string $token = null): Response {
        try {
            $client = new Client();
            $query_parameters['private_token'] = $token;
            $response = $client->request(
                $method,
                $uri . $resource,
                [
                    'query' => $query_parameters
                ]
            );

            return new SuccessResponse(json_decode($response->getBody()->getContents()));
        } catch(ClientException $exception) {
            return new ErrorResponse($exception->getMessage());
        }
    }

    private function getUserIdByAccessToken(string $token): ?string {
        $token_info = $this->getAccessTokenInfo($token);

        if($token_info instanceof ErrorResponse) {
            throw new GitLabException($token_info->data['error_message']);
        }

        return  $token_info->data->resource_owner_id;
    }
}