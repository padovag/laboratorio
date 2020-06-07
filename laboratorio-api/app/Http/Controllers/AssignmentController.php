<?php

namespace App\Http\Controllers;

use App\laboratorio\assignments\Assignment;
use App\laboratorio\assignments\AssignmentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends ApiController {
    private const IMPORT_FROM_PATTERN = '/http.*.\.git/';

    public function create(Request $request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'name' => 'required',
            'classroom_external_id' => 'required',
            'import_from' => 'regex:' . self::IMPORT_FROM_PATTERN
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $assigment = Assignment::create(
                $request['token'],
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
}
