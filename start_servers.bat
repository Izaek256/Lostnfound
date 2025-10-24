@echo off
echo Starting Lost & Found Servers...
echo.

echo Starting ServerA (User Management) on port 8080...
start "ServerA" cmd /k "cd /d C:\wamp64\www\Lostnfound\ServerA && php -S localhost:8080"

timeout /t 2

echo Starting ServerB (Item Management) on port 8081...
start "ServerB" cmd /k "cd /d C:\wamp64\www\Lostnfound\ServerB && php -S localhost:8081"

timeout /t 2

echo Starting ServerC (Frontend) on port 8082...
start "ServerC" cmd /k "cd /d C:\wamp64\www\Lostnfound\ServerC && php -S localhost:8082"

echo.
echo All servers started!
echo.
echo ServerA: http://localhost:8080
echo ServerB: http://localhost:8081  
echo ServerC: http://localhost:8082
echo.
echo Press any key to close this window...
pause >nul
