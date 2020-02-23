<?php

namespace App\Http\Controllers;

use App\laboratorio\classroom\Classroom;
use App\laboratorio\classroom\ClassroomException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassroomController extends ApiController {
    public function store(Request $request) {
        $validator = Validator::make($request->all(), ['name' => 'required']);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $classroom = new Classroom();
            $classroom->name = $request['name'];
            $classroom->description = $request['description'];
            $classroom->create();

            return $this->sendSuccessResponse((array)$classroom);
        } catch(ClassroomException $exception) {
            return $this->sendFailedResponse($exception->getMessage(), 400, (array) $classroom);
        }
    }
}
