@echo off
set PHP_BIN=%~dp0bin\php\php.exe
set COMPOSER_BIN=%~dp0bin\php\composer.phar

"%PHP_BIN%" "%COMPOSER_BIN%" %*

