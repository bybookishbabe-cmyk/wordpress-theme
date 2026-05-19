document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.favorite-card-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const formData = new FormData(form);
      const card = form.closest('.favorite-card-inner') || form.closest('.favorite-card');
      const titleEl = card ? card.querySelector('.favorite-card-title') : null;
      const title = titleEl ? titleEl.textContent.trim() : 'item';
      const select = form.querySelector('.favorite-card-select');
      const variantText = select ? select.options[select.selectedIndex]?.textContent.trim() || '' : '';
      const message = variantText ? `${title} — ${variantText}` : title;
      const productId = formData.get('product_id') || formData.get('id');
      const qty = formData.get('quantity') || 1;

      window
        .fetch(window.routes.cart_add_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Accept: 'application/json',
          },
          body: new URLSearchParams({
            action: 'woocommerce_ajax_add_to_cart',
            product_id: productId,
            quantity: qty,
            security: window.bbbData ? window.bbbData.nonce : '',
          }),
        })
        .then((response) => response.json())
        .then(() => showAddToCartToast(message))
        .catch((error) => console.error('Add to cart error:', error));
    });
  });

  function showAddToCartToast(text) {
    let existing = document.querySelector('.bb-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = 'bb-toast';
    toast.innerText = `added to cart: ${text}`;
    document.body.appendChild(toast);

    window.requestAnimationFrame(() => toast.classList.add('show'));
    window.setTimeout(() => {
      toast.classList.remove('show');
      window.setTimeout(() => toast.remove(), 300);
    }, 2200);
  }
});
