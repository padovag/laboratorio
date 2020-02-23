<?php

namespace App\Http\Controllers;

use App\GitLabUser;
use App\laboratorio\gitlab\GitLab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GitLabUserController extends ApiController {

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'user' => 'required|unique:gitlab_users',
            'password' => 'required',
            'redirect_url' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        $user = new GitLabUser();
        $user->name = $request['name'];
        $user->user = $request['user'];
        $user->password = $request['password'];
        $user->grr = $request['grr'];

        $user->save();

        $data = [
            'name' => $user->name,
            'user' => $user->user,
            'password' => $user->password,
            'grr' => $user->grr,
            'gitlab_authorization_redirect_link' => $this->getGitLabUrl($request['redirect_url'])
        ];

        return $this->sendSuccessResponse($data);
    }

    public function allow(Request $request) {
        $validator = Validator::make($request->all(), [
            'user' => 'required|exists:gitlab_users',
            'gitlab_code' => 'required|unique:gitlab_users'
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        $user = GitLabUser::where('user', $request['user'])->first();
        $user->gitlab_code = $request['gitlab_code'];
        $user->save();

        return $this->sendSuccessResponse(['user' => $user->user, 'gitlab_code' => $user->gitlab_code]);
    }

    public function authenticate(Request $request) {
        $validator = Validator::make($request->all(), [
            'user' => 'required|exists:gitlab_users',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        $user = GitLabUser::where('user', $request['user'])->first();
        if($user->password != $request['password']) {
            return $this->sendFailedResponse("Invalid password", $status = 400, ['user' => $user->user]);
        }

        return $this->sendSuccessResponse(['name' => $user->name, 'user' => $user->user, 'hasAuthorizedGitLab' => !empty($user->gitlab_code)]);
    }

    private function getGitLabUrl(string $redirect_url): string {
        $client_id = GitLab::getClientId();
        return "https://gitlab.com/oauth/authorize?client_id={$client_id}&redirect_uri={$redirect_url}&response_type=code";
    }
}
