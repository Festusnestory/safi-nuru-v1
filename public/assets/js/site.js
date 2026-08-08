(() => {
  const header = document.querySelector('[data-header]');
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  const mobileMenu = window.matchMedia('(max-width: 980px)');
  const syncHeader = () => header?.classList.toggle('scrolled', window.scrollY > 20);
  const setMenuState = (open, restoreFocus = false) => {
    if (!toggle || !nav) return;
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    const text = toggle.querySelector('.sr-only');
    if (text) text.textContent = open ? 'Close menu' : 'Open menu';
    nav.classList.toggle('open', open);
    document.body.classList.toggle('nav-open', open);
    if (mobileMenu.matches) {
      nav.toggleAttribute('inert', !open);
    } else {
      nav.removeAttribute('inert');
    }
    if (!open && restoreFocus) toggle.focus();
  };

  syncHeader();
  window.addEventListener('scroll', syncHeader, { passive: true });
  setMenuState(false);
  toggle?.addEventListener('click', () => {
    setMenuState(toggle.getAttribute('aria-expanded') !== 'true');
  });
  nav?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setMenuState(false)));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') {
      setMenuState(false, true);
    }
  });
  mobileMenu.addEventListener?.('change', () => setMenuState(false));

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    }), { threshold: .12 });
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
  } else {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
  }

  document.querySelectorAll('[data-inquiry-form]').forEach(form => {
    const button = form.querySelector('button[type="submit"]');
    const phone = form.querySelector('input[name="phone"]');
    const resetTransientState = () => {
      form.dataset.submitting = 'false';
      if (button) {
        button.disabled = false;
        button.textContent = button.dataset.defaultLabel || 'Send enquiry →';
      }
    };
    const setPhoneError = message => {
      const error = document.getElementById('phone-error');
      if (error) error.textContent = message;
      phone?.setAttribute('aria-invalid', message ? 'true' : 'false');
    };
    const validatePhone = () => {
      const value = phone?.value.trim() || '';
      const digits = value.replace(/\D/g, '');
      const validShape = /^\+?[0-9][0-9\s().-]{5,24}$/.test(value);
      const valid = validShape && digits.length >= 7 && digits.length <= 15;
      setPhoneError(valid ? '' : 'Enter a valid phone number using 7–15 digits.');
      return valid;
    };

    phone?.addEventListener('input', () => {
      if (phone.getAttribute('aria-invalid') === 'true') validatePhone();
    });
    form.querySelectorAll('input, select, textarea').forEach(field => {
      field.addEventListener('input', () => {
        const errorId = field.getAttribute('aria-describedby')?.split(/\s+/).find(id => id.endsWith('-error'));
        const error = errorId ? document.getElementById(errorId) : null;
        if (error && field !== phone) error.textContent = '';
        if (field !== phone) field.removeAttribute('aria-invalid');
      });
    });
    form.addEventListener('submit', event => {
      const phoneValid = validatePhone();
      if (!form.checkValidity() || !phoneValid || form.dataset.submitting === 'true') {
        event.preventDefault();
        form.querySelector(':invalid, [aria-invalid="true"]')?.focus();
        return;
      }
      form.dataset.submitting = 'true';
      if (button) {
        button.disabled = true;
        button.textContent = 'Sending…';
      }
    });
    window.addEventListener('pageshow', resetTransientState);
    resetTransientState();
    form.querySelector('[data-form-error]')?.focus();
  });
})();
