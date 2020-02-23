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
}