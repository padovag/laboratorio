<?php

namespace App\Http\Controllers;

use App\laboratorio\gitlab\GitUser;
use App\laboratorio\TokenException;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends ApiController {

    public function register(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
            }

            $git_user = $this->getUserFromCode($request['code']);
            $user = $this->store($git_user, $request['registration_number']);

            return $this->sendSuccessResponse((array) $user->getAttributes());
        } catch(TokenException $exception) {
            return $this->sendIncorrectTokenResponse($request['code']);
        }
    }

    public function authenticate(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
            }

            $git_user = $this->getUserFromCode($request['code']);
            $user = User::where('username', $git_user->username)->first();
            $registration_error = null;
            if(!$user) {
                $registration_error = "User {$git_user->username} is not registered on our database. Register it now.";
            }

            return $this->sendSuccessResponse(['username' => $git_user->username, 'registration_error' => $registration_error]);
        } catch(TokenException $exception) {
            return $this->sendIncorrectTokenResponse($request['token']);
        }
    }

    private function store(GitUser $git_user, ?string $registration_number) {
        $user = new User();
        $user->username = $git_user->username;
        $user->name = $git_user->name;
        $user->registration_number = $registration_number;

        $user->save();

        return $user;
    }

    private function getUserFromCode(string $code): ?GitUser {
        $git_user = (new GitUser())->getFromProvider($code);

        if(is_null($git_user)) {
            throw new TokenException("Could not retrieve user from code");
        }

        return $git_user;
    }
}
