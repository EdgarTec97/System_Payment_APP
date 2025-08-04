<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\DashboardController;
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

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authentication routes
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    // Register
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    // Email verification
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
});

// Email verification (can be accessed by authenticated users)
Route::get('/email/verify/{token}', [VerificationController::class, 'verify'])->name('verification.verify');

// Logout (authenticated users only)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Protected routes (require authentication and email verification)
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Basic user dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        
        // User management routes will be added here
        // Product management routes will be added here
        // Order management routes will be added here
    });
    
    // Support routes
    Route::middleware('role:support,admin')->prefix('support')->name('support.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'support'])->name('dashboard');
        
        // Product management routes will be added here
        // Order management routes will be added here
    });
    
    // Basic user routes
    Route::middleware('role:basic,support,admin')->group(function () {
        // Product viewing routes will be added here
        // Order creation routes will be added here
        // Profile management routes will be added here
    });
});

// API routes for AJAX requests
Route::middleware(['auth', 'verified'])->prefix('api')->name('api.')->group(function () {
    // API routes will be added here for AJAX functionality
});

