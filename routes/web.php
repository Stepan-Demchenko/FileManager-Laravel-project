<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/ls', 'FileManagerController@content');
Route::get('/tree', 'FileManagerController@tree');
Route::post('/mkdir', 'FileManagerController@createDirectory');
Route::post('/delete', 'FileManagerController@delete');
Route::post('/rename', 'FileManagerController@rename');
Route::get('/preview', 'FileManagerController@preview');
Route::get('/download', 'FileManagerController@download');
Route::post('/upload', 'FileManagerController@upload');