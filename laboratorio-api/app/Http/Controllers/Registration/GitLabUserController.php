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
            'password' => $user->password
        ];

        return $this->sendSuccessResponse($data);
    }
}
