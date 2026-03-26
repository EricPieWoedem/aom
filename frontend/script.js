const menuToggle = document.querySelector('.menu-toggle');
const navLinks = document.querySelector('.nav-links');

if (menuToggle && navLinks) {
  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('is-open');
    menuToggle.setAttribute('aria-label', navLinks.classList.contains('is-open') ? 'Close menu' : 'Open menu');
  });
}

const themeToggle = document.querySelector('.theme-toggle');
const html = document.documentElement;
const THEME_KEY = 'landing-theme';

/** Default session price (same for all markets unless you add per-product logic). */
const PAWAPAY_DEFAULT_AMOUNT = '10';

const paymentModal = document.getElementById('payment-modal');
const payCountry = document.getElementById('pay-country');
const payOperator = document.getElementById('pay-operator');
const payCurrency = document.getElementById('pay-currency');
const payCurrencyWrap = document.getElementById('pay-currency-wrap');
const payCurrencyDisplay = document.getElementById('pay-currency-display');
const payAmountInput = document.getElementById('pay-amount');
const payModalCancel = document.getElementById('pay-modal-cancel');
const payModalContinue = document.getElementById('pay-modal-continue');
const payModalBackdrop = paymentModal?.querySelector('[data-close-modal]');

const currentYear = document.getElementById('current-year');
if (currentYear) currentYear.textContent = String(new Date().getFullYear());

function setTheme(theme) {
  html.setAttribute('data-theme', theme || 'dark');
  if (themeToggle) {
    themeToggle.setAttribute('aria-label', theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode');
  }
  try {
    localStorage.setItem(THEME_KEY, theme || 'dark');
  } catch (e) {}
}

function initTheme() {
  try {
    const saved = localStorage.getItem(THEME_KEY);
    if (saved === 'light' || saved === 'dark') setTheme(saved);
  } catch (e) {}
}

if (themeToggle) {
  initTheme();
  themeToggle.addEventListener('click', () => {
    const current = html.getAttribute('data-theme');
    const next = current === 'light' ? 'dark' : 'light';
    setTheme(next);
  });
}

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    const href = this.getAttribute('href');
    if (href === '#') return;
    const target = document.querySelector(href);
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});

function getMarkets() {
  return window.PAWAPAY_MARKETS || {};
}

function getCountryLabels() {
  return window.PAWAPAY_COUNTRY_LABELS || {};
}

function populateCountrySelect() {
  if (!payCountry) return;
  const markets = getMarkets();
  const labels = getCountryLabels();
  const codes = Object.keys(markets).sort((a, b) =>
    (labels[a] || a).localeCompare(labels[b] || b, undefined, { sensitivity: 'base' })
  );
  payCountry.innerHTML = '<option value="">Select country</option>';
  codes.forEach((code) => {
    const opt = document.createElement('option');
    opt.value = code;
    opt.textContent = labels[code] || code;
    payCountry.appendChild(opt);
  });
}

function onCountryChange() {
  const markets = getMarkets();
  const code = payCountry?.value;
  payOperator.innerHTML = '<option value="">Select network</option>';
  payCurrency.innerHTML = '';
  if (!code || !markets[code]) {
    payCurrencyWrap.hidden = true;
    return;
  }
  const { operators, currencies } = markets[code];
  operators.forEach((op) => {
    const opt = document.createElement('option');
    opt.value = op.value;
    opt.textContent = op.label;
    payOperator.appendChild(opt);
  });
  if (currencies.length > 1) {
    payCurrencyWrap.hidden = false;
    currencies.forEach((c) => {
      const opt = document.createElement('option');
      opt.value = c;
      opt.textContent = c;
      payCurrency.appendChild(opt);
    });
  } else {
    payCurrencyWrap.hidden = true;
    payCurrency.innerHTML = `<option value="${currencies[0]}">${currencies[0]}</option>`;
  }
  syncCurrencyDisplay();
}

function getSelectedCurrencyCode() {
  const markets = getMarkets();
  const code = payCountry?.value;
  if (!code || !markets[code]) return '';
  const { currencies } = markets[code];
  if (currencies.length > 1 && payCurrency?.value) return payCurrency.value;
  return currencies[0] || '';
}

function syncCurrencyDisplay() {
  const c = getSelectedCurrencyCode();
  if (payCurrencyDisplay) payCurrencyDisplay.textContent = c || '—';
}

function closePaymentModal() {
  if (!paymentModal) return;
  paymentModal.classList.remove('is-open');
  paymentModal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

function openPaymentModal() {
  if (!paymentModal) {
    alert('Payment form is not available. Check that payment-markets.js loaded.');
    return;
  }
  if (!payCountry?.options?.length || payCountry.options.length <= 1) populateCountrySelect();
  if (payAmountInput) payAmountInput.value = PAWAPAY_DEFAULT_AMOUNT;
  paymentModal.classList.add('is-open');
  paymentModal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

if (payCountry) payCountry.addEventListener('change', onCountryChange);
if (payOperator) payOperator.addEventListener('change', syncCurrencyDisplay);
if (payCurrency) payCurrency.addEventListener('change', syncCurrencyDisplay);

if (payModalCancel) payModalCancel.addEventListener('click', closePaymentModal);
if (payModalBackdrop) payModalBackdrop.addEventListener('click', closePaymentModal);
if (payModalContinue) {
  payModalContinue.addEventListener('click', () => {
    const country = payCountry?.value;
    const preferredProvider = payOperator?.value;
    if (!country || !preferredProvider) {
      alert('Please select your country and mobile money network.');
      return;
    }
    const currency = getSelectedCurrencyCode();
    if (!currency) {
      alert('Could not determine currency for this country.');
      return;
    }
    const amountRaw = payAmountInput?.value;
    const amount = (amountRaw ?? '').toString().trim();
    if (amount === '' || isNaN(Number(amount))) {
      alert('Please enter a valid amount.');
      return;
    }
    if (Number(amount) <= 0) {
      alert('Amount must be greater than 0.');
      return;
    }
    payNow({
      amount,
      currency,
      description: 'Language Coaching Session',
      country,
      preferredProvider
    });
  });
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && paymentModal?.classList.contains('is-open')) closePaymentModal();
});

async function payNow(opts) {
  const payload = {
    amount: opts.amount ?? PAWAPAY_DEFAULT_AMOUNT,
    currency: opts.currency,
    description: opts.description ?? 'Language Coaching Session',
    country: opts.country,
    preferredProvider: opts.preferredProvider
  };

  try {
    const res = await fetch('/api/create-payment-page.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const raw = await res.text();
    let data = null;
    try {
      data = JSON.parse(raw);
    } catch (parseErr) {
      console.error('Non-JSON response from payment API:', raw);
      alert('Payment server returned an invalid response. Please check PHP configuration.');
      return;
    }

    if (res.ok && data.redirectUrl) {
      closePaymentModal();
      window.location.href = data.redirectUrl;
      return;
    }

    console.error('Payment API error:', data);
    const details =
      data?.details ||
      (data?.error && data?.hint ? `${data.error} — ${data.hint}` : null) ||
      (data?.returnUrlPreview
        ? `Bad return URL (check PAWAPAY_RETURN_URL). Preview: ${data.returnUrlPreview}`
        : null) ||
      data?.failureReason?.failureMessage ||
      data?.failureReason?.description ||
      data?.failureReason?.errorMessage ||
      data?.failureReason?.errorCode ||
      data?.failureReason?.failureCode ||
      data?.result?.failureReason?.failureMessage ||
      data?.result?.failureReason?.description ||
      data?.result?.failureReason?.errorMessage ||
      'Unknown payment API error';
    alert(`Payment failed: ${details}`);
  } catch (err) {
    console.error(err);
    alert('Error connecting to payment server.');
  }
}
