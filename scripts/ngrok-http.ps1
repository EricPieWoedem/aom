# Forwards public HTTPS traffic to your local PHP server.
# Prerequisites:
#   1. Sign up at https://dashboard.ngrok.com
#   2. Run ONCE (saves token to ngrok.yaml — no window, no URL; that is normal):
#        ngrok config add-authtoken YOUR_TOKEN
#   3. Start PHP from repo root: php -S localhost:8000
# Then run THIS script. The PUBLIC https URL appears HERE in the terminal
# (and often at http://127.0.0.1:4040). Set PAWAPAY_RETURN_URL from that host.

param(
    [int]$Port = 8000
)

if (-not (Get-Command ngrok -ErrorAction SilentlyContinue) -and -not (Get-Command ngrok.cmd -ErrorAction SilentlyContinue)) {
    Write-Error "ngrok not found on PATH. Install: winget install Ngrok.Ngrok"
    exit 1
}

Write-Host "Tunnel: public https -> http://localhost:$Port"
Write-Host "One-time auth: ngrok config add-authtoken YOUR_TOKEN (from https://dashboard.ngrok.com)"
Write-Host "Then set PAWAPAY_RETURN_URL to: https://<this-host>/witlevels_pawapay_php_sdk/success.php`n"
ngrok http $Port
