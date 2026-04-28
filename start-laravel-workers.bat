@echo off

echo ==============================
echo Laravel Workers Starting...
echo ==============================

rem cd /d C:\laragon\www\ton-projet

echo Starting Queue Worker...
start "Queue Worker" cmd /k php artisan queue:work --sleep=3 --tries=1 >> storage\logs\queue.log 2>&1

echo Starting Scheduler...
start "Scheduler" cmd /k php artisan schedule:work >> storage\logs\scheduler.log 2>&1


echo All services started.
pause
