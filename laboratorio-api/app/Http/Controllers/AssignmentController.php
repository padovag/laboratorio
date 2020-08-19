<?php

namespace App\Http\Controllers;

use App\laboratorio\assignments\Assignment;
use App\laboratorio\assignments\AssignmentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AssignmentController extends ApiController {
    private const IMPORT_FROM_PATTERN = '/http.*.\.git/';

    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'name' => 'required',
            'classroom_external_id' => 'required',
            'import_from' => 'regex:' . self::IMPORT_FROM_PATTERN
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $assigment = Assignment::create(
                $request['code'],
                $request['name'],
                $request['description'],
                $request['classroom_external_id'],
                $request['import_from']
            );
            return $this->sendSuccessResponse((array) $assigment);
        } catch(AssignmentException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function get(Request $request) {
        $validator = Validator::make($request->all(), ['code' => 'required']);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $assigment = Assignment::get($request['code'], $request['id']);

            return $this->sendSuccessResponse((array) $assigment);
        } catch(AssignmentException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function accept(Request $request) {
        $validator = Validator::make($request->all(), ['code' => 'required']);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $accepted_assignment = Assignment::accept($request['code'], $request['id']);

            return $this->sendSuccessResponse((array) $accepted_assignment);
        } catch(AssignmentException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function getStudents(Request $request) {
        $validator = Validator::make($request->all(), ['code' => 'required', 'assignment_status' => Rule::in(['done'])]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $students = Assignment::getStudents($request['code'], $request['id'], $request['assignment_status']);

            return $this->sendSuccessResponse((array) $students);
        } catch(AssignmentException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function list(Request $request) {
        $validator = Validator::make($request->all(), ['code' => 'required']);
        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $assignments = Assignment::list($request['id'], $request['code']);

            return $this->sendSuccessResponse((array) $assignments);
        } catch(AssignmentException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }
}
