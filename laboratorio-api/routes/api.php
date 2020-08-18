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

Route::get('/classroom', 'ClassroomController@list');
Route::post('/classroom', 'ClassroomController@create');
Route::post('/classroom/members', 'ClassroomController@add');
Route::get('/classroom/{id}', 'ClassroomController@get');

Route::get('/classroom/{id}/assignments', 'AssignmentController@list');
Route::post('/assignment', 'AssignmentController@create');
Route::get('/assignment/{id}', 'AssignmentController@get');
Route::post('/assignment/{id}/accept', 'AssignmentController@accept');
Route::get('/assignment/{id}/students', 'AssignmentController@getStudents');
