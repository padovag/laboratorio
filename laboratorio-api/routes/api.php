<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/user/register', 'UserController@register');
Route::post('/user/auth', 'UserController@authenticate');
Route::get('/users', 'UserController@list');
Route::post('/user/current', 'UserController@authenticate');
Route::get('/user/current/classrooms', 'ClassroomController@list');
Route::get('/user', 'UserController@get');

Route::get('/classroom', 'ClassroomController@list');
Route::post('/classroom', 'ClassroomController@create');
Route::post('/classroom/members', 'ClassroomController@add');
Route::get('/classroom/{id}', 'ClassroomController@get');
Route::delete('/classroom/{id}', 'ClassroomController@delete');

Route::get('/classroom/{id}/assignments', 'AssignmentController@list');
Route::post('/assignment', 'AssignmentController@create');
Route::get('/assignment/{id}', 'AssignmentController@get');
Route::delete('/assignment/{id}', 'AssignmentController@delete');
Route::post('/assignment/{id}/accept', 'AssignmentController@accept');
Route::get('/assignment/{id}/is_accepted', 'AssignmentController@isAccepted');
Route::get('/assignment/{id}/students', 'AssignmentController@getStudents');
Route::get('/assignment/{id}/close', 'AssignmentController@close');
Route::post('/assignment/{id}/grade', 'AssignmentController@grade');
Route::get('/assignment/{id}/grades', 'AssignmentController@getGrades');
