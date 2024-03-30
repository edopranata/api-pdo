<?php

use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\DeliveryOrder\DOSettingController;
use App\Http\Controllers\Api\Permission\PermissionController;
use App\Http\Controllers\Api\Role\RoleController;
use App\Http\Controllers\Api\User\UserChangePasswordController;
use App\Http\Controllers\Api\User\UserController;
use App\Http\Controllers\Api\User\UserInfoController;
use App\Http\Controllers\Api\User\UserLoginController;
use App\Http\Controllers\Api\User\UserLogoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->post('user', UserInfoController::class);
Route::middleware('auth:sanctum')->post('change-password', UserChangePasswordController::class);
Route::middleware('auth:sanctum')->post('logout', UserLogoutController::class);
Route::post('login', UserLoginController::class);

Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::group(['prefix' => 'management', 'as' => 'management.'], function () {

        Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
            Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:admin.management.users.index,api');
            Route::post('/', [UserController::class, 'store'])->name('createUser')->middleware('permission:admin.management.users.createUser,api');
            Route::patch('/{user}', [UserController::class, 'update'])->name('updateUser')->middleware('permission:admin.management.users.updateUser,api');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('deleteUser')->middleware('permission:admin.management.users.deleteUser,api');
            Route::post('/{user}', [UserController::class, 'update'])->name('resetPassword')->middleware('permission:admin.management.users.resetPassword,api');
        });

        Route::group(['prefix' => 'permissions', 'as' => 'permissions.'], function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index')->middleware('permission:admin.management.permissions.index,api');
            Route::post('/', [PermissionController::class, 'sync'])->name('syncPermissions')->middleware('permission:admin.management.permissions.syncPermissions,api');
            Route::get('/{id}/view', [PermissionController::class, 'view'])->name('viewPermission')->middleware('permission:admin.management.permissions.viewPermission,api');
            Route::post('/{id}/view', [PermissionController::class, 'viewRolesUsers']);
        });

        Route::group(['prefix' => 'roles', 'as' => 'roles.'], function () {
            Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('permission:admin.management.roles.index,api');
            Route::get('/{role}/view', [RoleController::class, 'show'])->name('viewRole')->middleware('permission:admin.management.roles.viewRole,api');
            Route::post('/{role}/view', [RoleController::class, 'showDetails']);
            Route::patch('/{role}/view', [RoleController::class, 'addPermissionsToRole'])->name('addPermissionsToRole')->middleware('permission:admin.management.roles.addPermissionsToRole,api');

            Route::post('/', [RoleController::class, 'store'])->name('createRole')->middleware('permission:admin.management.roles.createRole,api');
            Route::patch('/{role}', [RoleController::class, 'update'])->name('updateRole')->middleware('permission:admin.management.roles.updateRole,api');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('deleteRole')->middleware('permission:admin.management.roles.deleteRole,api');
        });

        Route::group(['prefix' => 'DOSetting', 'as' => 'DOSetting.'], function () {
            Route::get('/', [DOSettingController::class, 'index'])->name('index')->middleware('permission:admin.management.DOSetting.index,api');
        });
    });
    Route::group(['prefix' => 'masterData', 'as' => 'masterData.'], function () {
        Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
            Route::get('', [CustomerController::class, 'index'])->name('index')->middleware('permission:admin.masterData.customer.index,api');
            Route::post('/', [CustomerController::class, 'store'])->name('createCustomer')->middleware('permission:admin.masterData.customer.createCustomer,api');
            Route::patch('/{customer}', [CustomerController::class, 'update'])->name('updateCustomer')->middleware('permission:admin.masterData.customer.updateCustomer,api');
            Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('deleteCustomer')->middleware('permission:admin.masterData.customer.deleteCustomer,api');
        });
    });
})->middleware('auth:sanctum');

