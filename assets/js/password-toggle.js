(function () {
  document.querySelectorAll('.password-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
      var wrap = button.closest('.password-field');
      var input = wrap ? wrap.querySelector('input') : null;
      if (!input) return;

      var visible = input.type === 'password';
      input.type = visible ? 'text' : 'password';
      wrap.classList.toggle('is-visible', visible);
      button.setAttribute('aria-pressed', visible ? 'true' : 'false');
      var label = visible ? 'Sembunyikan password' : 'Tampilkan password';
      button.setAttribute('aria-label', label);
      button.title = label;
    });
  });
})();
