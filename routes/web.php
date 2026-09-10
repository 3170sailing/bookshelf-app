<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [BookController::class, 'index'])->name('books.index');

Route::middleware('auth')->group(function () {
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
});

Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

Route::view('/ranking', 'ranking.index')->name('ranking.index');

Route::get('/favorites', [FavoriteController::class, 'index'])
    ->middleware('auth')
    ->name('favorites.index');

Route::view('/genres', 'genres.index')->name('genres.index');

Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])
    ->middleware('auth')
    ->name('favorites.toggle');
