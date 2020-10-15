<?php

namespace App\Http\Controllers;

use App\laboratorio\assignments\Assignment;
use App\laboratorio\assignments\AssignmentException;
use App\laboratorio\assignments\ClosedAssignment;
use App\laboratorio\classroom\Classroom;
use App\laboratorio\gitlab\GitUser;
use App\User;
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
            'import_from' => 'regex:' . self::IMPORT_FROM_PATTERN,
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        $due_date = new \DateTime($request['due_date']);
        $now = new \DateTime();
        if($due_date < $now) {
            return $this->sendFailedResponse("Validation error", $status = 400, ['You cannot add a past date as due date']);
        }

        try {
            $assigment = Assignment::create(
                $request['code'],
                $request['name'],
                $request['description'],
                $request['classroom_external_id'],
                $request['import_from'],
                $request['due_date'],
                $request['visibility']
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
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'filter_by' => Rule::in([Assignment::CLOSED_STATUS, Assignment::OPENED_STATUS]),
        ]);
        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $assignments = Assignment::list($request['id'], $request['filter_by'], $request['code']);

            return $this->sendSuccessResponse((array) $assignments);
        } catch(AssignmentException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function close(Request $request) {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        try {
            $assignment = Assignment::getStudents($request['code'], $request['id']);
            $students_that_accepted = array_map(function($student) {
                return $student->student_user;
            }, $assignment->students);
            $students_from_class = array_map(function($student) {
                return $student->name;
            }, Classroom::get($request['code'], $assignment->classroom_external_id)->members);

            $students_that_didnt_accept = array_filter($students_from_class, function($student) use ($students_that_accepted) {
                return !in_array($student, $students_that_accepted);
            });

            $students_that_accepted_grades = array_map(function($student) {
                return ['student' => $student, 'grade' => null];
            }, $students_that_accepted);

            $students_with_zero = array_map(function($student) {
                return ['student' => $student, 'grade' => 0];
            }, $students_that_didnt_accept);

            $students_grades = array_merge($students_that_accepted_grades, $students_with_zero);

            $closed_assignments = [];
            foreach($students_grades as $student_grade) {
                $closed_assignment = new ClosedAssignment();
                $closed_assignment->external_id = $assignment->assignment_external_id;
                $closed_assignment->user_id = User::where('name', $student_grade['student'])->first()->id;
                $closed_assignment->classroom_id = $assignment->classroom_external_id;
                $closed_assignment->grade = $student_grade['grade'];
                $closed_assignment->save();
                $closed_assignments[] =$closed_assignment;
            }

            return $this->sendSuccessResponse((array) $closed_assignments);
        } catch(AssignmentException $exception) {
            return $this->sendFailedResponse($exception->getMessage());
        }
    }

    public function grade(Request $request) {
        $validator = Validator::make($request->all(), [
            'grades' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendFailedResponse("Validation error", $status = 400, $validator->errors()->messages());
        }

        foreach($request['grades'] as $student_grade) {
            $user = User::where('name', $student_grade['student_name'])->first();
            $closed_assignment = ClosedAssignment::where(['external_id' => $request['id'], 'user_id' => $user->id])->first();
            $closed_assignment->grade = $student_grade['grade'];
            $closed_assignment->save();
        }

        return $this->sendSuccessResponse(['The assignments have been graded']);
    }

    public function getGrades(Request $request) {
        $closed_assignments = ClosedAssignment::where('external_id', $request['id'])->get();

        $students_grades = [];
        foreach($closed_assignments as $student_closed_assignment) {
            $user = User::find($student_closed_assignment->user_id);
            $students_grades[] = ['student' => $user->name, 'grade' => $student_closed_assignment->grade];
        }

        return $this->sendSuccessResponse($students_grades);

    }
}
