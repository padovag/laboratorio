<?php

namespace App\Http\Controllers;

use App\laboratorio\classroom\Classroom;
use App\laboratorio\classroom\ClassroomException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassroomController extends ApiController {
    public function create(Request $request) {
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

    public function add(Request $request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'members' => 'required',
            'classroom_external_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $members = Classroom::addMembers(
                $request['token'],
                $request['classroom_external_id'],
                explode(",", trim($request['members']))
            );

            return $this->sendSuccessResponse($members);
        } catch(ClassroomException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function list(Request $request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $classrooms = Classroom::list($request['token']);

            return $this->sendSuccessResponse($classrooms);
        } catch(ClassroomException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }
}
