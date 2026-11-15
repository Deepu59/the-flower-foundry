document.addEventListener('DOMContentLoaded', () => {
  const menuButton = document.querySelector('.menu-toggle');
  const navigation = document.querySelector('#main-nav');
  if (menuButton && navigation) {
    menuButton.addEventListener('click', () => {
      const open = navigation.classList.toggle('open');
      menuButton.setAttribute('aria-expanded', String(open));
    });
  }

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-qty-step]');
    if (!button) return;
    const input = button.parentElement.querySelector('.qty-input');
    if (!input) return;
    const min = Number(input.min || 1);
    const max = Number(input.max || 20);
    const current = Number(input.value || min);
    input.value = String(Math.min(max, Math.max(min, current + Number(button.dataset.qtyStep))));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  const kind = document.querySelector('#kind');
  const category = document.querySelector('#category');
  if (kind && category) {
    const updateCategories = () => {
      const type = kind.value;
      const options = Array.from(category.options);
      options.forEach((option) => {
        if (option.dataset.kind) option.hidden = option.dataset.kind !== type;
      });
      const selected = category.selectedOptions[0];
      if (!selected || selected.dataset.kind !== type) {
        const first = options.find((option) => option.dataset.kind === type);
        if (first) category.value = first.value;
      }
    };
    kind.addEventListener('change', updateCategories);
    updateCategories();
  }
});
