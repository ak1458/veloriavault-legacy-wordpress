/* Merchant repeaters use input fields rather than editable JSON. */
document.addEventListener('click', (event) => {
  const button = event.target.closest('button');
  if (!button) return;
  if (button.classList.contains('veloria-add')) {
    const repeater = button.closest('.veloria-repeater');
    const next = Number(repeater.dataset.next);
    const source = [...repeater.children].find((node) => node.tagName === 'TEMPLATE');
    const fragment = source.content.cloneNode(true);
    fragment.querySelectorAll('[name]').forEach((field) => { field.name = field.name.replaceAll(repeater.dataset.token, String(next)); });
    const rows = [...repeater.children].find((node) => node.classList.contains('veloria-rows'));
    rows.append(fragment);
    repeater.dataset.next = String(next + 1);
    if (window.jQuery) window.jQuery(document.body).trigger('wc-enhanced-select-init');
  }
  if (button.classList.contains('veloria-remove')) button.closest('.veloria-row').remove();
  if (button.classList.contains('veloria-up') || button.classList.contains('veloria-down')) {
    const row = button.closest('.veloria-row');
    if (button.classList.contains('veloria-up') && row.previousElementSibling) row.previousElementSibling.before(row);
    else if (button.classList.contains('veloria-down') && row.nextElementSibling) row.nextElementSibling.after(row);
  }
  if (button.classList.contains('veloria-media') && window.wp?.media) {
    const input = button.previousElementSibling;
    const frame = wp.media({ title: 'Choose photo', library: { type: 'image' }, multiple: false });
    frame.on('select', () => { input.value = frame.state().get('selection').first().toJSON().url; input.dispatchEvent(new Event('change', { bubbles: true })); });
    frame.open();
  }
});
