<?php

namespace App\Http\Controllers;

use App\laboratorio\classroom\Classroom;
use App\laboratorio\classroom\ClassroomException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassroomController extends ApiController {
    public function store(Request $request) {
        $validator = Validator::make($request->all(), ['name' => 'required', 'token' => 'required']);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $classroom = Classroom::create(
                $request['token'],
                $request['name'],
                $request['description'],
                explode(",", trim($request['members']))
            );

            return $this->sendSuccessResponse((array) $classroom);
        } catch(ClassroomException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }
}
