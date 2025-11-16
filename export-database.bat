@echo off
echo ==========================================
echo Export de la base de donnees
echo ==========================================
echo.

REM Configuration
set MYSQL_PATH=c:\laragon\bin\mysql\mysql-8.0.30-winx64\bin
set DB_NAME=cosmetics_db
set DB_USER=root
set DB_PASS=
set OUTPUT_FILE=database-export-%date:~-4,4%%date:~-7,2%%date:~-10,2%.sql

echo Exportation de la base de donnees %DB_NAME%...
"%MYSQL_PATH%\mysqldump.exe" -u %DB_USER% %DB_NAME% > %OUTPUT_FILE%

if %errorlevel% == 0 (
    echo.
    echo ✓ Export reussi: %OUTPUT_FILE%
    echo.
    echo Fichier pret a etre importe sur Hostinger
    echo Taille du fichier:
    dir %OUTPUT_FILE% | find "%OUTPUT_FILE%"
) else (
    echo.
    echo × Erreur lors de l'export
)

echo.
pause
