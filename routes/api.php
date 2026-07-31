<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CompaniesController;
use App\Http\Controllers\Api\V1\CustomFieldsController;
use App\Http\Controllers\Api\V1\NotesController;
use App\Http\Controllers\Api\V1\OpportunitiesController;
use App\Http\Controllers\Api\V1\PeopleController;
use App\Http\Controllers\Api\V1\TasksController;
use App\Http\Middleware\EnsureTokenHasAbility;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetApiTeamContext;
use App\Http\Resources\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware([ForceJsonResponse::class, 'auth:sanctum', 'throttle:api', EnsureTokenHasAbility::class, SetApiTeamContext::class])
    ->group(function (): void {
        Route::get('user', function (Request $request) {
            return new UserResource($request->user());
        });

        Route::apiResource('companies', CompaniesController::class);
        Route::apiResource('people', PeopleController::class);
        Route::apiResource('opportunities', OpportunitiesController::class);
        Route::apiResource('tasks', TasksController::class);
        Route::apiResource('notes', NotesController::class);

        Route::get('custom-fields', [CustomFieldsController::class, 'index'])->name('custom-fields.index');
    });

// Meta WhatsApp Cloud API Webhooks
Route::get('whatsapp/webhook/{accountId}', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('whatsapp/webhook/{accountId}', [\App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handle'])->name('whatsapp.webhook.handle');

