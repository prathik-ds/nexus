
@echo off
echo ====================================================
echo   FUSIONVERSE LOCAL NETWORK HOSTING
echo ====================================================
echo.
echo Your WiFi IP is: 192.168.0.108
echo.
echo Instructions for other devices:
echo 1. Connect to the same WiFi
echo 2. Open browser and go to: http://192.168.0.108:8080
echo.
echo Starting server... (Press Ctrl+C to stop)
php -S 0.0.0.0:8080
pause
