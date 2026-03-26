# Language Coaching & Consulting Frontend

This frontend now uses a PHP endpoint from `witlevels_pawapay_php_sdk` to create a PawaPay hosted checkout session.

## Local setup

Serve both folders from the same PHP-enabled host root so this relative call works:

- `frontend/script.js` -> `../witlevels_pawapay_php_sdk/api/create-payment-page.php`

Example setup:

1. Put `frontend/` and `witlevels_pawapay_php_sdk/` under the same web root.
2. Open `frontend/index.html` through your web server URL (not file://).
3. Ensure these environment variables are available to PHP:
   - `PAWAPAY_API_KEY` (sandbox token)
   - `PAWAPAY_ENV=sandbox`
   - `PAWAPAY_RETURN_URL` — full **HTTPS** URL to `witlevels_pawapay_php_sdk/success.php` (use [ngrok](https://ngrok.com/) or similar; PawaPay usually rejects `http://localhost` as `returnUrl`)
   - Optional: `APP_ORIGIN=http://localhost:8000` (or your frontend origin)

## Ngrok (needed for `PAWAPAY_RETURN_URL`)

PawaPay validates `returnUrl`; **`http://localhost/...` is usually rejected**. Use a temporary public HTTPS URL:

1. **Save your authtoken (one-time — silent)**  
   Sign up at [ngrok dashboard](https://dashboard.ngrok.com). Under **Your Authtoken**, copy the token. In PowerShell or CMD:

   ```powershell
   ngrok config add-authtoken YOUR_TOKEN_HERE
   ```

   This only writes `ngrok.yaml`. It does **not** open a window and does **not** show a public URL — that is expected.

2. **Terminal A — start the tunnel** (this is when you see the URL):

   ```powershell
   cd C:\Users\marci\Desktop\aom
   .\scripts\ngrok-http.ps1
   ```

   Or run `scripts\ngrok-http.bat` / `ngrok http 8000`. Leave this running. In that **same terminal** ngrok prints **Forwarding** lines with `https://….ngrok-free.app` — copy that host. You can also open **http://127.0.0.1:4040** in a browser while the tunnel is running to see requests and the public URL.

3. **Terminal B — PHP** (from repo root `aom/`). Use that host in `PAWAPAY_RETURN_URL`, then start the server:

   ```powershell
   $env:PAWAPAY_API_KEY = "your_sandbox_token"
   $env:PAWAPAY_ENV = "sandbox"
   $env:APP_ORIGIN = "http://localhost:8000"
   $env:PAWAPAY_RETURN_URL = "https://YOUR-NGROK-HOST.ngrok-free.app/witlevels_pawapay_php_sdk/success.php"
   php -S localhost:8000
   ```

   If ngrok gives you a new hostname next time, update `PAWAPAY_RETURN_URL` and restart PHP.

4. **Browser** — open `http://localhost:8000/frontend/index.html` and run a payment test. After PawaPay redirects, you should land on `success.php` through ngrok (use **Back to site** to return to the landing page).

Helper scripts: `scripts/ngrok-http.ps1`, `scripts/ngrok-http.bat`.

## Payment behavior

When the user clicks any booking button:

1. Frontend calls `create-payment-page.php` with amount/currency/description.
2. PHP calls PawaPay `POST /v2/paymentpage`.
3. Browser is redirected to PawaPay `redirectUrl`.
4. After payment, user returns to `witlevels_pawapay_php_sdk/success.php` (via your HTTPS `PAWAPAY_RETURN_URL`).

## Notes

- **Country & network:** Pay buttons open a modal. Markets are defined in `frontend/payment-markets.js`. Default amount is `PAWAPAY_DEFAULT_AMOUNT` in `frontend/script.js`.
- **Sandbox vs live:** There is no separate “sandbox-only” country list in code. Which countries work is determined by **your PawaPay account** (dashboard configuration). Confirm with PawaPay’s [Active configuration](https://docs.pawapay.io/v2/api-reference/toolkit/active-configuration) API or **Merchant dashboard** → enable the countries/providers you need for sandbox testing. If a country is not enabled, initiation may fail with an authorisation or provider error—pick another enabled market or ask PawaPay to enable it.
- **Hosted payment page:** The API accepts **`country`** and optional **`metadata.preferredProvider`** (we send the network you picked for your own reporting/callbacks). PawaPay’s hosted UI may still show all mobile money options for that **country**; the customer confirms the wallet. To lock a specific wallet you’d use **`phoneNumber`** in the API instead (not wired in the modal yet).
- Replace missing images under `frontend/images/` or update image paths in `frontend/index.html`.
