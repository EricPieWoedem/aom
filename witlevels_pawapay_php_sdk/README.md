# wit PawaPay PHP SDK

## Requirements

- PawaPay account: [https://www.pawapay.io/log-in](https://www.pawapay.io/log-in)
- Sandbox or live API token
- PHP with cURL extension enabled

## Configuration

This SDK now reads configuration from environment variables:

- `PAWAPAY_API_KEY` -> your API token
- `PAWAPAY_ENV` -> `sandbox` or `live` (defaults to `sandbox`)
- `APP_ORIGIN` -> optional allowed frontend origin for CORS in API endpoint
- `PAWAPAY_RETURN_URL` -> **full HTTPS URL** to `success.php` after payment (required for real tests if `http://localhost` is rejected)
- `PAWAPAY_DEFAULT_COUNTRY` -> optional **ISO 3166-1 alpha-3** (e.g. `CIV`, `GHA`) used when the JSON body omits `country`; required by PawaPay when `amountDetails` is sent unless `phoneNumber` is sent instead

Example (ngrok):

```text
PAWAPAY_RETURN_URL=https://abcd-123-45.ngrok-free.app/witlevels_pawapay_php_sdk/success.php
```

Point ngrok at the same port as `php -S` (e.g. 8000). The path must match where `success.php` is served.

Do not hardcode tokens in source code.

## Available flows

### 1) Hosted payment page (frontend integration)

Uses Merchant API **v2**: `POST /v2/paymentpage` with `depositId`, `returnUrl`, `amountDetails`, and `reason`.

Use `api/create-payment-page.php` from your frontend.

After checkout, PawaPay redirects to `returnUrl` with a **`depositId` query parameter** (not `token`). `success.php` and `verify.php` accept either `depositId` or `token`.

Example request body:

```json
{
  "amount": "10",
  "currency": "XOF",
  "description": "Language Coaching Session",
  "country": "CIV",
  "preferredProvider": "MTN_MOMO_CIV"
}
```

With a fixed amount, PawaPay also requires **`country`** (ISO alpha-3) or **`phoneNumber`** (MSISDN digits). Or set `PAWAPAY_DEFAULT_COUNTRY` on the server.

Optional: **`preferredProvider`** (e.g. `MTN_MOMO_GHA`) is sent as **metadata** for your logs/callbacks. It does not override PawaPay’s hosted UI network list.

**Which countries in sandbox?** Enable markets in the PawaPay dashboard; verify via [Active configuration](https://docs.pawapay.io/v2/api-reference/toolkit/active-configuration). Not code-limited.

Response:

```json
{
  "redirectUrl": "https://...",
  "transactionId": "..."
}
```

### 2) Mobile money deposit (SDK method)

```php
include_once 'classes/PawaPay.php';
$pawapay = new PawaPay();

$pay = $pawapay->deposit(
    "Payment description here",
    "5",
    "ZMW",
    "ZMB",
    "AIRTEL_OAPI_ZMB",
    "Comfort Chambeshi",
    "witlevels04@gmail.com",
    "260972927679"
);
```

Supported countries/correspondents:
[https://docs.pawapay.io/using_the_api#correspondents](https://docs.pawapay.io/using_the_api#correspondents)

## Verify transaction

```php
include 'classes/PawaPay.php';
$pawapay = new PawaPay();
$verify = $pawapay->verifyTransaction("deposit_id_here");
```
