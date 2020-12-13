<?php

namespace App\Http\Controllers;

use App\Mail\InviteStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailerController extends ApiController {

    public function sendInvite(Request $request) {
        Mail::to($request['email'])->send(new InviteStudent());
    }

}
