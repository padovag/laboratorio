<?php


namespace App\Http\Controllers;

class ApiController extends Controller {
    public function sendSuccessResponse(array $dataset = null) {
        return response()->json([
            'response_status' => 'success',
            'data' => $dataset
        ], $status = 200);
    }

    public function sendFailedResponse(string $error, string $status = '400', array $dataset = null) {
        return response()->json([
            'response_status' => 'fail',
            'error' => $error,
            'data' => $dataset
        ], $status);
    }
}