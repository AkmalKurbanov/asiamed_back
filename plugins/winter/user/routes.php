<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth', 'can:manage-users']], function () {
    // Маршрут для создания врача
    Route::get('/admin/doctors/create', 'Winter\User\Controllers\DoctorController@create')->name('admin.doctors.create');
    Route::post('/admin/doctors', 'Winter\User\Controllers\DoctorController@store')->name('admin.doctors.store');

    // Маршрут для редактирования врача
    Route::get('/admin/doctors/{doctor}/edit', 'Winter\User\Controllers\DoctorController@edit')->name('admin.doctors.edit');
    Route::post('/admin/doctors/{doctor}', 'Winter\User\Controllers\DoctorController@update')->name('admin.doctors.update');

    // Маршрут для создания пациента
    Route::get('/admin/patients/create', 'Winter\User\Controllers\PatientController@create')->name('admin.patients.create');
    Route::post('/admin/patients', 'Winter\User\Controllers\PatientController@store')->name('admin.patients.store');
});
