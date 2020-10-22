<?php

namespace App\Http\Controllers;

use App\laboratorio\classroom\Classroom;
use App\laboratorio\classroom\ClassroomException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassroomController extends ApiController {
    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'code' => 'required',
            'background_color' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $classroom = Classroom::create(
                $request['code'],
                str_replace(' ', '', $request['name']),
                $request['description'],
                explode(",", trim($request['members'])),
                $request['background_color'],
                $request['visibility']
            );

            return $this->sendSuccessResponse((array) $classroom);
        } catch(\Exception $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function add(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'members' => 'required',
            'classroom_external_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $members = Classroom::addMembers(
                $request['code'],
                $request['classroom_external_id'],
                explode(",", trim($request['members']))
            );

            return $this->sendSuccessResponse($members);
        } catch(\Exception $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function list(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $classrooms = Classroom::list($request['code']);

            return $this->sendSuccessResponse($classrooms);
        } catch(\Exception $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function get(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $classroom = Classroom::get($request['code'], $request['id']);

            return $this->sendSuccessResponse((array)$classroom);
        } catch(\Exception $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }
}
