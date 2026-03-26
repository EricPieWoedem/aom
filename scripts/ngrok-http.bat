@echo off
REM Forward https to local PHP (default port 8000).
REM One-time (silent, saves token only): ngrok config add-authtoken YOUR_TOKEN
REM Then run this file — the https://.... URL appears in THIS window.
if "%~1"=="" (
  ngrok http 8000
) else (
  ngrok http %~1
)
