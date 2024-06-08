<?php

use App\Http\Controllers\Api\BlankController;
use App\Http\Controllers\Api\Cash\CashController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeliveryOrder\DeliveryOrderController;
use App\Http\Controllers\Api\Factory\FactoryController;
use App\Http\Controllers\Api\Income\OrderIncomeController;
use App\Http\Controllers\Api\Invoice\InvoiceController;
use App\Http\Controllers\Api\Loan\LoanController;
use App\Http\Controllers\Api\Permission\PermissionController;
use App\Http\Controllers\Api\Price\FactoryPriceController;
use App\Http\Controllers\Api\Report\InvoiceDataController;
use App\Http\Controllers\Api\Report\ReportCashController;
use App\Http\Controllers\Api\Report\ReportDeliveryOrderController;
use App\Http\Controllers\Api\Report\TransactionReportController;
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
    Route::post('/', [DashboardController::class, 'index'])->name('index');
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
        Route::group(['prefix' => 'cash', 'as' => 'cash.'], function () {
            Route::get('/', [CashController::class, 'index'])->name('index')->middleware('permission:admin.management.cash.index,api');
            Route::post('/{user}/giveCash', [CashController::class, 'giveCash'])->name('giveCash')->middleware('permission:admin.management.cash.giveCash,api');
            Route::post('/{user}/takeCash', [CashController::class, 'takeCash'])->name('takeCash')->middleware('permission:admin.management.cash.takeCash,api');
            Route::get('/{user}/details', [CashController::class, 'show'])->name('cashDetails')->middleware('permission:admin.management.cash.cashDetails,api');
        });
        Route::group(['prefix' => 'price', 'as' => 'price.'], function () {
            Route::get('/', [FactoryPriceController::class, 'index'])->name('index')->middleware('permission:admin.management.price.index,api');
            Route::post('/{factory}', [FactoryPriceController::class, 'store'])->name('savePrice')->middleware('permission:admin.management.price.savePrice,api');
            Route::patch('/{price}', [FactoryPriceController::class, 'update']);

        });
    });
    Route::group(['prefix' => 'masterData', 'as' => 'masterData.'], function () {
        Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
            Route::get('', [CustomerController::class, 'index'])->name('index')->middleware('permission:admin.masterData.customer.index,api');
            Route::post('/', [CustomerController::class, 'store'])->name('createCustomer')->middleware('permission:admin.masterData.customer.createCustomer,api');
            Route::patch('/{customer}', [CustomerController::class, 'update'])->name('updateCustomer')->middleware('permission:admin.masterData.customer.updateCustomer,api');
            Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('deleteCustomer')->middleware('permission:admin.masterData.customer.deleteCustomer,api');
            Route::post('/firstLoan', [BlankController::class, 'blank'])->name('firstLoan')->middleware('permission:admin.masterData.customer.firstLoan,api');

        });

        Route::group(['prefix' => 'factory', 'as' => 'factory.'], function () {
            Route::get('', [FactoryController::class, 'index'])->name('index')->middleware('permission:admin.masterData.factory.index,api');
            Route::post('/', [FactoryController::class, 'store'])->name('createFactory')->middleware('permission:admin.masterData.factory.createFactory,api');
            Route::patch('/{factory}', [FactoryController::class, 'update'])->name('updateFactory')->middleware('permission:admin.masterData.factory.updateFactory,api');
            Route::delete('/{factory}', [FactoryController::class, 'destroy'])->name('deleteFactory')->middleware('permission:admin.masterData.factory.deleteFactory,api');
        });
    });
    Route::group(['prefix' => 'transaction', 'as' => 'transaction.'], function () {
        Route::group(['prefix' => 'loan', 'as' => 'loan.'], function () {
            Route::get('', [LoanController::class, 'index'])->name('index')->middleware('permission:admin.transaction.loan.index,api');
            Route::post('/{customer}/addLoan', [LoanController::class, 'addLoan'])->name('addLoan')->middleware('permission:admin.transaction.loan.addLoan,api');
            Route::post('/{customer}/payLoan', [LoanController::class, 'payLoan'])->name('payLoan')->middleware('permission:admin.transaction.loan.payLoan,api');
            Route::get('/{invoice}', [InvoiceDataController::class, 'show'])->name('print')->middleware('permission:admin.transaction.loan.print,api');
            Route::get('/{customer}/details', [LoanController::class, 'show'])->name('loanDetails')->middleware('permission:admin.transaction.loan.loanDetails,api');
        });

        Route::group(['prefix' => 'order', 'as' => 'order.'], function () {
            Route::get('', [DeliveryOrderController::class, 'index'])->name('index')->middleware('permission:admin.transaction.order.index,api');
            Route::post('{factory}', [DeliveryOrderController::class, 'store'])->name('createOrder')->middleware('permission:admin.transaction.order.createOrder,api');
            Route::patch('{order}', [DeliveryOrderController::class, 'update'])->name('updateOrder')->middleware('permission:admin.transaction.order.updateOrder,api');
            Route::delete('{order}', [DeliveryOrderController::class, 'destroy'])->name('deleteOrder')->middleware('permission:admin.transaction.order.deleteOrder,api');
        });

        Route::group(['prefix' => 'invoice', 'as' => 'invoice.'], function () {
            Route::get('', [InvoiceController::class, 'index'])->name('index')->middleware('permission:admin.transaction.invoice.index,api');
            Route::get('{customer}', [InvoiceController::class, 'show'])->name('showInvoice')->middleware('permission:admin.transaction.invoice.showInvoice,api');
            Route::post('{customer}', [InvoiceController::class, 'store'])->name('createInvoice')->middleware('permission:admin.transaction.invoice.createInvoice,api');
            Route::get('{invoice}/print', [InvoiceDataController::class, 'show'])->name('print')->middleware('permission:admin.transaction.invoice.print,api');

        });
        Route::group(['prefix' => 'income', 'as' => 'income.'], function () {
            Route::get('', [OrderIncomeController::class, 'index'])->name('index')->middleware('permission:admin.transaction.income.index,api');
            Route::post('{factory}', [OrderIncomeController::class, 'store'])->name('createIncome')->middleware('permission:admin.transaction.income.createIncome,api');

        });
    });
    Route::group(['prefix' => 'report', 'as' => 'report.'], function () {
        Route::group(['prefix' => 'invoiceReport', 'as' => 'invoiceReport.'], function () {
            Route::get('', [InvoiceDataController::class, 'index'])->name('index')->middleware('permission:admin.report.invoiceReport.index,api');
            Route::get('{invoice}', [InvoiceDataController::class, 'show'])->name('print')->middleware('permission:admin.report.invoiceReport.print,api');
        });
        Route::group(['prefix' => 'DOReport', 'as' => 'DOReport.'], function () {
            Route::get('', [ReportDeliveryOrderController::class, 'index'])->name('index')->middleware('permission:admin.report.DOReport.index,api');
            Route::get('{factory}', [ReportDeliveryOrderController::class, 'show'])->name('print')->middleware('permission:admin.report.DOReport.print,api');
            Route::post('{factory}', [ReportDeliveryOrderController::class, 'export'])->name('export')->middleware('permission:admin.report.DOReport.export,api');
        });

        Route::group(['prefix' => 'cashReport', 'as' => 'cashReport.'], function () {
            Route::get('', [BlankController::class, 'index'])->name('index')->middleware('permission:admin.report.cashReport.index,api');
            Route::get('dailyCash', [ReportCashController::class, 'show'])->name('dailyCash')->middleware('permission:admin.report.cashReport.dailyCash,api');
            Route::get('todayCash', [ReportCashController::class, 'show'])->name('todayCash')->middleware('permission:admin.report.cashReport.todayCash,api');

        });

        Route::group(['prefix' => 'transactionReport', 'as' => 'transactionReport.'], function () {
            Route::get('', [BlankController::class, 'index'])->name('index')->middleware('permission:admin.report.transactionReport.index,api');
            Route::get('dailyTransaction', [TransactionReportController::class, 'show'])->name('dailyTransaction')->middleware('permission:admin.report.transactionReport.dailyTransaction,api');
            Route::get('todayTransaction', [TransactionReportController::class, 'show'])->name('todayTransaction')->middleware('permission:admin.report.transactionReport.todayTransaction,api');

        });
    });
})->middleware('auth:sanctum');



