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
Route::post('/ls', 'FileManagerController@content');
Route::put('/ls', 'FileManagerController@rename');
Route::post('/download', 'FileManagerController@download');
//Route::get('/tree', 'FileManagerController@tree');
Route::post('/move', 'FileManagerController@changeDirectory');
Route::post('/mkdir', 'FileManagerController@createDirectory');
Route::post('/delete', 'FileManagerController@delete');
//Route::get('/preview', 'FileManagerController@preview');
Route::post('/upload', 'FileManagerController@upload');
Route::post('/copy', 'FileManagerController@copy');