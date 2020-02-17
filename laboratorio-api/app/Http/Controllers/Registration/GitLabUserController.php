<?php

namespace App\Http\Controllers;

use App\GitLabUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GitLabUserController extends ApiController {

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:gitlab_users',
            'user' => 'required|unique:gitlab_users',
            'redirect_url' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        $user = new GitLabUser();
        $user->name = $request['name'];
        $user->user = $request['user'];
        $user->password = $request['password'];

        $user->save();

        $data = [
            'name' => $user->name,
            'user' => $user->user,
            'password' => $user->password,
            'gitlab_authorization_redirect_link' => $this->getGitLabUrl($request['redirect_url'])
        ];

        return $this->sendSuccessResponse($data);
    }

    public function allow(Request $request) {
        $validator = Validator::make($request->all(), [
            'user' => 'required',
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

    private function getGitLabUrl(string $redirect_url): string {
        return "https://gitlab.com/oauth/authorize?client_id=11b725a12fba56f24b81dbebaa93781aeabe6042c1804cde36c5738a7ad30d6e&redirect_uri={$redirect_url}&response_type=code";
    }
}
