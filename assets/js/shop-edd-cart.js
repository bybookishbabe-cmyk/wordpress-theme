(function () {
  'use strict';

  var purchaseFormSelector = '.bbb-shop .edd_download_purchase_form, .bbb-product .edd_download_purchase_form';
  var pendingAttr = 'data-bbb-cart-pending';
  var activeForm = null;
  var activeToken = '';

  function closestForm(element) {
    return element ? element.closest(purchaseFormSelector) : null;
  }

  function statusNode(form) {
    var node = form.querySelector('.bbb-edd-cart-status');
    if (!node) {
      node = document.createElement('p');
      node.className = 'bbb-edd-cart-status';
      node.setAttribute('aria-live', 'polite');
      var wrapper = form.querySelector('.edd_purchase_submit_wrapper') || form;
      wrapper.insertAdjacentElement('afterend', node);
    }
    return node;
  }

  function setStatus(form, message) {
    statusNode(form).textContent = message || '';
  }

  function clearLoading(form) {
    form.removeAttribute(pendingAttr);
    form.classList.remove('is-bbb-cart-pending');

    form.querySelectorAll('.edd-add-to-cart').forEach(function (button) {
      button.disabled = false;
      button.removeAttribute('data-edd-loading');
      button.classList.remove('is-bbb-adding');
    });
  }

  function allForms() {
    return Array.prototype.slice.call(document.querySelectorAll(purchaseFormSelector));
  }

  function clearInactiveStatuses(active) {
    allForms().forEach(function (form) {
      if (form === active) {
        return;
      }

      form.removeAttribute(pendingAttr);
      form.classList.remove('is-bbb-cart-pending', 'is-bbb-cart-added');

      var status = form.querySelector('.bbb-edd-cart-status');
      if (status) {
        status.textContent = '';
      }

      form.querySelectorAll('.edd-add-to-cart').forEach(function (button) {
        button.classList.remove('is-bbb-adding');
      });
    });
  }

  function completeActiveCartAdd() {
    if (!activeForm || activeForm.getAttribute(pendingAttr) !== activeToken) {
      return;
    }

    var form = activeForm;
    clearLoading(form);
    form.classList.add('is-bbb-cart-added');

    var checkout = form.querySelector('.edd_go_to_checkout');
    if (checkout) {
      checkout.textContent = 'checkout';
    }

    setStatus(form, 'added to cart. checkout is ready.');
    activeForm = null;
    activeToken = '';
  }

  function ensureHiddenInput(form, name, value) {
    var input = form.querySelector('input[type="hidden"][name="' + name + '"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      form.appendChild(input);
    }
    input.value = value;
  }

  function fallbackSubmit(form) {
    if (!form || form.hasAttribute('data-bbb-cart-fallback')) {
      return;
    }

    form.setAttribute('data-bbb-cart-fallback', 'true');
    clearLoading(form);
    if (form === activeForm) {
      activeForm = null;
      activeToken = '';
    }
    setStatus(form, 'opening checkout...');

    var actionInput = form.querySelector('.edd_action_input');
    if (actionInput) {
      actionInput.value = 'add_to_cart';
    } else {
      ensureHiddenInput(form, 'edd_action', 'add_to_cart');
    }

    ensureHiddenInput(form, 'edd_purchase_download', 'add to cart');
    window.HTMLFormElement.prototype.submit.call(form);
  }

  document.addEventListener('change', function (event) {
    if (!event.target.matches('.bbb-shop-card__sizeSelect')) {
      return;
    }

    var form = closestForm(event.target);
    if (!form) {
      return;
    }

    var hidden = form.querySelector('input[type="hidden"][name="edd_options[price_id][]"]');
    if (hidden) {
      hidden.value = event.target.value;
    }
  });

  document.addEventListener('click', function (event) {
    var button = event.target.closest('.edd-add-to-cart:not(.edd-no-js)');
    var form = closestForm(button);
    if (!button || !form) {
      return;
    }

    var pendingToken = String(Date.now());
    activeForm = form;
    activeToken = pendingToken;
    clearInactiveStatuses(form);
    form.setAttribute(pendingAttr, pendingToken);
    form.classList.add('is-bbb-cart-pending');
    form.classList.remove('is-bbb-cart-added');
    button.classList.add('is-bbb-adding');
    setStatus(form, 'adding to cart...');

    window.setTimeout(function () {
      if (form.getAttribute(pendingAttr) !== pendingToken) {
        return;
      }

      clearLoading(form);
      fallbackSubmit(form);
    }, 12000);
  }, true);

  if (window.jQuery) {
    window.jQuery(document.body).on('edd_cart_item_added', function () {
      completeActiveCartAdd();
    });

    window.jQuery(document).ajaxError(function (_event, _xhr, settings) {
      var data = settings && settings.data ? String(settings.data) : '';
      if (data.indexOf('edd_add_to_cart') === -1) {
        return;
      }

      if (activeForm && activeForm.hasAttribute(pendingAttr)) {
        fallbackSubmit(activeForm);
      }
    });
  }
})();
