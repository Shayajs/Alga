<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\AccueilController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RegleController as AdminRegleController;
use App\Http\Controllers\Admin\TacheController as AdminTacheController;
use App\Http\Controllers\AffectationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompletionController;
use App\Http\Controllers\RegleController;
use App\Http\Controllers\TableauController;
use Illuminate\Support\Facades\Route;

Route::get('/', AccueilController::class)->name('accueil');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'show'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->name('login.store');
    Route::post('/connexion/etat', [AuthController::class, 'etat'])->name('login.etat');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');

    Route::get('/planning', [TableauController::class, 'aujourdhui'])->name('tableau.aujourdhui');
    Route::get('/historique', [TableauController::class, 'historique'])->name('tableau.historique');
    Route::post('/lot-autre/fait', [CompletionController::class, 'volerLot'])->name('completions.voler-lot');
    Route::post('/affectations/{affectation}/fait', [CompletionController::class, 'store'])->name('completions.store');
    Route::post('/affectations/{affectation}/volee', [CompletionController::class, 'voler'])->name('completions.voler');
    Route::put('/affectations/{affectation}', [AffectationController::class, 'update'])->name('affectations.update');

    Route::get('/absences', [AbsenceController::class, 'index'])->name('absences.index');
    Route::post('/absences', [AbsenceController::class, 'store'])->name('absences.store');

    Route::get('/regles', [RegleController::class, 'index'])->name('regles.index');
});

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('index');
    Route::post('/planning', [AdminTacheController::class, 'generer'])->name('planning.generer');
    Route::resource('taches', AdminTacheController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['taches' => 'tache']);
    Route::resource('regles', AdminRegleController::class)->only(['index', 'store', 'update', 'destroy']);
});
