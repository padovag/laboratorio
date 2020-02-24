<?php

namespace Tests\laboratorio\gitlab;

use App\laboratorio\util\http\SuccessResponse;
use Tests\TestCase;

class GitLabTest extends TestCase {
    public function testCreateGitLabGroup() {
        $name = "laboratorio-teste";
        $description = "Teste criação grupo GitLab";
        $gitlab_client = new GitLabForTests();
        $gitlab_client->mockSuccessResponse((object) ['name' => $name, 'description' => $description]);

        $response = $gitlab_client->createGroup($name, $description);

        $this->assertInstanceOf(SuccessResponse::class, $response);
        $this->assertEquals($name, $response->data->name);
        $this->assertEquals($description, $response->data->description);
    }

    public function testAddMembersToGroup() {
        $user_id = 1215858;
        $access_level = 30; //developer access
        $group_id = 7244702;

        $gitlab_client = new GitLabForTests();
        $gitlab_client->mockSuccessResponse((object) ['id' => $user_id, 'name' => 'Teste da Silva']);
        $response = $gitlab_client->addMemberToGroup($user_id, $group_id, $access_level);

        $this->assertInstanceOf(SuccessResponse::class, $response);
        $this->assertEquals($user_id, $response->data->id);
        $this->assertEquals('Teste da Silva', $response->data->name);
    }
}