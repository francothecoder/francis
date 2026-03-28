(function () {
  const root = document.documentElement;
  const storedTheme = localStorage.getItem('fk-theme');
  if (storedTheme) {
    root.setAttribute('data-theme', storedTheme);
  }

  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      localStorage.setItem('fk-theme', next);
    });
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.reveal').forEach((item) => observer.observe(item));

  document.querySelectorAll('.prop-button').forEach((button) => {
    button.addEventListener('click', () => {
      button.classList.add('pulse-once');
      setTimeout(() => button.classList.remove('pulse-once'), 450);
    });
  });
})();
