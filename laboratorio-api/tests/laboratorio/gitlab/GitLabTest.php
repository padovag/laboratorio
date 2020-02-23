<?php

namespace Tests\laboratorio\gitlab;

use App\laboratorio\util\http\SuccessResponse;
use Tests\TestCase;

class GitLabTest extends TestCase {
    public function testCreateGitLabGroup() {
        $name = "laboratorio-teste";
        $path = "laboratorio-teste-path";
        $description = "Teste criação grupo GitLab";
        $gitlab_client = new GitLabForTests();
        $gitlab_client->mockSuccessResponse((object) ['name' => $name, 'path' => $path, 'description' => $description]);

        $response = $gitlab_client->createGroup($name, $path, $description);

        $this->assertInstanceOf(SuccessResponse::class, $response);
        $this->assertEquals($name, $response->data->name);
        $this->assertEquals($path, $response->data->path);
        $this->assertEquals($description, $response->data->description);
    }
}