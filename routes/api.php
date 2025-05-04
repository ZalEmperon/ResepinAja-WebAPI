<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/resepbaru', [RecipeController::class, 'showRecipeNewest']);
Route::get('/reseppopuler', [RecipeController::class, 'showRecipeBest']);
Route::get('/ratingresep/{id_resep}', [ UserController::class, 'showRating']);
Route::get('/detailresep/{id_resep}', [ RecipeController::class, 'showDetailRecipe']);
Route::get('/resepsendiri/{id_user}', [ RecipeController::class, 'showRecipeSelf']);
Route::get('/resepcari/{kunci_cari}', [ RecipeController::class, 'showRecipeSearch']);
Route::get('/profil/{id_user}', [ UserController::class, 'showProfile']);
Route::get('/user-rating/{id_resep}/{id_user}', [UserController::class, 'getUserRating']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/save-recipe', [UserController::class, 'saveRecipe']);
    Route::post('/unsave-recipe', [UserController::class, 'unsaveRecipe']);
    Route::post('/submit-rating', [UserController::class, 'addRating']);
    Route::delete('/del-rating', [UserController::class, 'DeleteUserRating']);
    Route::get('/check-saved/{id_user}/{id_resep}', [UserController::class, 'checkSaved']);
    Route::post('/edit-profile', [UserController::class, 'editProfile']);

    Route::get('/tersimpanresep/{id_user}', [ RecipeController::class, 'showSavedRecipe']);
    Route::post('/addresep', [RecipeController::class, 'addRecipe']);
    Route::put('/updateresep/{id_resep}', [RecipeController::class, 'updateResep']);
    Route::delete('/deleteresep/{id_resep}', [RecipeController::class, 'deleteResep']);
    Route::post('/logout', [AuthController::class, 'logoutToken']);
});

