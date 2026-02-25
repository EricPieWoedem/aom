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
