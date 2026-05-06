@echo off
setlocal

REM バックアップディレクトリの作成
set BACKUP_DIR=C:\apps\harairo\backups
set DATE_TIME=%date:~0,4%%date:~5,2%%date:~8,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set DATE_TIME=%DATE_TIME: =0%
set BACKUP_PATH=%BACKUP_DIR%\backup_%DATE_TIME%

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
mkdir "%BACKUP_PATH%"

REM dataディレクトリのバックアップ
xcopy /E /I /Y "C:\apps\harairo\data" "%BACKUP_PATH%\data"

echo Backup completed: %BACKUP_PATH%

REM 30日以上前のバックアップを削除
forfiles /p "%BACKUP_DIR%" /m backup_* /d -30 /c "cmd /c if @isdir==TRUE rmdir /s /q @path" 2>nul

endlocal
