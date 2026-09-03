<?php

use App\Http\Controllers\ActorAuthController;
use App\Http\Controllers\AdminReadController;
use App\Http\Controllers\AgentRdvVerificationController;
use App\Http\Controllers\AgentScannerController;
use App\Http\Controllers\ResponsableRdvController;
use App\Http\Controllers\SouscripteurRdvController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('souscripteur')->name('souscripteur.')->group(function () {
    Route::get('login', [ActorAuthController::class, 'showLogin'])
        ->defaults('actor', 'souscripteur')
        ->name('login');
    Route::post('login', [ActorAuthController::class, 'login'])
        ->defaults('actor', 'souscripteur')
        ->name('login.store');
    Route::middleware('auth:souscripteur')->group(function () {
        Route::get('dashboard', [ActorAuthController::class, 'dashboard'])
            ->defaults('actor', 'souscripteur')
            ->name('dashboard');
        Route::get('profil', [ActorAuthController::class, 'souscripteurProfile'])
            ->name('profil');
        Route::get('rdvs', [SouscripteurRdvController::class, 'index'])
            ->name('rdvs.index');
        Route::get('rdvs/indisponibilites', [SouscripteurRdvController::class, 'indisponibilites'])
            ->name('rdvs.indisponibilites');
        Route::get('rdvs/create', [SouscripteurRdvController::class, 'create'])
            ->name('rdvs.create');
        Route::get('rdvs/{hashid}/fiche', [SouscripteurRdvController::class, 'fiche'])
            ->name('rdvs.fiche');
        Route::get('rdvs/{hashid}/pdf', [SouscripteurRdvController::class, 'pdf'])
            ->name('rdvs.pdf');
        Route::post('rdvs', [SouscripteurRdvController::class, 'store'])
            ->name('rdvs.store');
        Route::post('logout', [ActorAuthController::class, 'logout'])
            ->defaults('actor', 'souscripteur')
            ->name('logout');
    });
});

Route::prefix('responsable')->name('responsable.')->group(function () {
    Route::get('login', [ActorAuthController::class, 'showLogin'])
        ->defaults('actor', 'responsable')
        ->name('login');
    Route::post('login', [ActorAuthController::class, 'login'])
        ->defaults('actor', 'responsable')
        ->name('login.store');
    Route::middleware('auth:responsable')->group(function () {
        Route::get('dashboard', [ActorAuthController::class, 'dashboard'])
            ->defaults('actor', 'responsable')
            ->name('dashboard');
        Route::get('rdvs', [ResponsableRdvController::class, 'index'])
            ->name('rdvs.index');
        Route::patch('rdvs/{rdv}/accepter', [ResponsableRdvController::class, 'accepter'])
            ->name('rdvs.accepter');
        Route::patch('rdvs/{rdv}/completer', [ResponsableRdvController::class, 'completer'])
            ->name('rdvs.completer');
        Route::post('logout', [ActorAuthController::class, 'logout'])
            ->defaults('actor', 'responsable')
            ->name('logout');
    });
});

Route::prefix('agent')->name('agent.')->group(function () {
    Route::get('login', [ActorAuthController::class, 'showLogin'])
        ->defaults('actor', 'agent')
        ->name('login');
    Route::post('login', [ActorAuthController::class, 'login'])
        ->defaults('actor', 'agent')
        ->name('login.store');
    Route::middleware('auth:agent')->group(function () {
        Route::get('dashboard', [ActorAuthController::class, 'dashboard'])
            ->defaults('actor', 'agent')
            ->name('dashboard');
        Route::get('scanner', [AgentScannerController::class, 'show'])
            ->name('scanner');
        Route::get('rdvs/{hashid}/verification', [AgentRdvVerificationController::class, 'show'])
            ->name('rdvs.verification');
        Route::patch('rdvs/{hashid}/valider', [AgentRdvVerificationController::class, 'valider'])
            ->name('rdvs.valider');
        Route::post('logout', [ActorAuthController::class, 'logout'])
            ->defaults('actor', 'agent')
            ->name('logout');
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [ActorAuthController::class, 'showLogin'])
        ->defaults('actor', 'admin')
        ->name('login');
    Route::post('login', [ActorAuthController::class, 'login'])
        ->defaults('actor', 'admin')
        ->name('login.store');
    Route::middleware('auth:admin')->group(function () {
        Route::get('dashboard', [ActorAuthController::class, 'dashboard'])
            ->defaults('actor', 'admin')
            ->name('dashboard');
        Route::get('souscripteurs', [AdminReadController::class, 'souscripteurs'])
            ->name('souscripteurs.index');
        Route::get('responsables/create', [AdminReadController::class, 'createResponsable'])
            ->name('responsables.create');
        Route::post('responsables', [AdminReadController::class, 'storeResponsable'])
            ->name('responsables.store');
        Route::get('responsables/{responsable}/edit', [AdminReadController::class, 'editResponsable'])
            ->name('responsables.edit');
        Route::patch('responsables/{responsable}', [AdminReadController::class, 'updateResponsable'])
            ->name('responsables.update');
        Route::delete('responsables/{responsable}', [AdminReadController::class, 'destroyResponsable'])
            ->name('responsables.destroy');
        Route::get('responsables/{responsable}/mot-de-passe', [AdminReadController::class, 'editResponsablePassword'])
            ->name('responsables.password.edit');
        Route::patch('responsables/{responsable}/mot-de-passe', [AdminReadController::class, 'updateResponsablePassword'])
            ->name('responsables.password.update');
        Route::get('responsables', [AdminReadController::class, 'responsables'])
            ->name('responsables.index');
        Route::get('agents/create', [AdminReadController::class, 'createAgent'])
            ->name('agents.create');
        Route::post('agents', [AdminReadController::class, 'storeAgent'])
            ->name('agents.store');
        Route::get('agents/{agent}/edit', [AdminReadController::class, 'editAgent'])
            ->name('agents.edit');
        Route::patch('agents/{agent}', [AdminReadController::class, 'updateAgent'])
            ->name('agents.update');
        Route::delete('agents/{agent}', [AdminReadController::class, 'destroyAgent'])
            ->name('agents.destroy');
        Route::get('agents/{agent}/mot-de-passe', [AdminReadController::class, 'editAgentPassword'])
            ->name('agents.password.edit');
        Route::patch('agents/{agent}/mot-de-passe', [AdminReadController::class, 'updateAgentPassword'])
            ->name('agents.password.update');
        Route::get('agents', [AdminReadController::class, 'agents'])
            ->name('agents.index');
        Route::get('admins/create', [AdminReadController::class, 'createAdmin'])
            ->name('admins.create');
        Route::post('admins', [AdminReadController::class, 'storeAdmin'])
            ->name('admins.store');
        Route::get('admins/{admin}/edit', [AdminReadController::class, 'editAdmin'])
            ->name('admins.edit');
        Route::patch('admins/{admin}', [AdminReadController::class, 'updateAdmin'])
            ->name('admins.update');
        Route::delete('admins/{admin}', [AdminReadController::class, 'destroyAdmin'])
            ->name('admins.destroy');
        Route::get('admins/{admin}/mot-de-passe', [AdminReadController::class, 'editAdminPassword'])
            ->name('admins.password.edit');
        Route::patch('admins/{admin}/mot-de-passe', [AdminReadController::class, 'updateAdminPassword'])
            ->name('admins.password.update');
        Route::get('admins', [AdminReadController::class, 'admins'])
            ->name('admins.index');
        Route::get('rdvs', [AdminReadController::class, 'rdvs'])
            ->name('rdvs.index');
        Route::post('logout', [ActorAuthController::class, 'logout'])
            ->defaults('actor', 'admin')
            ->name('logout');
    });
});
