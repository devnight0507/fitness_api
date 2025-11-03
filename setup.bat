@echo off
echo ======================================
echo Fitness App Backend Setup
echo ======================================
echo.

echo Step 1: Installing Laravel Sanctum...
call composer require laravel/sanctum
echo.

echo Step 2: Publishing Sanctum configuration...
call php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
echo.

echo Step 3: Creating storage directories...
mkdir storage\app\public\videos\workouts 2>nul
mkdir storage\app\public\videos\lessons 2>nul
mkdir storage\app\public\images\thumbnails 2>nul
mkdir storage\app\public\images\avatars 2>nul
mkdir storage\app\public\images\nutrition 2>nul
echo Storage directories created.
echo.

echo Step 4: Creating storage link...
call php artisan storage:link
echo.

echo Step 5: Running migrations...
call php artisan migrate
echo.

echo ======================================
echo Setup Complete!
echo ======================================
echo.
echo Next steps:
echo 1. Start the server: php artisan serve --host=0.0.0.0 --port=8000
echo 2. Test API: http://localhost:8000/api
echo.
pause
