(() => {
  function getModal(id) {
    return id ? document.getElementById(id) : null;
  }

  function isOpen(modal) {
    if (!modal) return false;
    return !modal.classList.contains('hidden') && !modal.hasAttribute('hidden');
  }

  function updateBodyScrollLock() {
    const hasOpen = Array.from(document.querySelectorAll('[data-modal-root]'))
      .some((modal) => isOpen(modal));
    document.body.classList.toggle('overflow-hidden', hasOpen);
  }

  function openModal(id) {
    const modal = getModal(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.removeAttribute('hidden');
    modal.setAttribute('aria-hidden', 'false');
    updateBodyScrollLock();
  }

  function closeModal(id) {
    const modal = getModal(id);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('hidden', '');
    modal.setAttribute('aria-hidden', 'true');
    updateBodyScrollLock();
  }

  function closeTopModal() {
    const openModals = Array.from(document.querySelectorAll('[data-modal-root]'))
      .filter((modal) => isOpen(modal));
    if (!openModals.length) return;
    const last = openModals[openModals.length - 1];
    closeModal(last.id);
  }

  function initModalEvents() {
    document.addEventListener('click', (event) => {
      const openTrigger = event.target.closest('[data-modal-open]');
      if (openTrigger) {
        const id = openTrigger.getAttribute('data-modal-open');
        openModal(id);
        return;
      }

      const closeTrigger = event.target.closest('[data-modal-close]');
      if (closeTrigger) {
        const id = closeTrigger.getAttribute('data-modal-close');
        if (id) {
          closeModal(id);
        } else {
          const modal = closeTrigger.closest('[data-modal-root]');
          if (modal) closeModal(modal.id);
        }
        return;
      }

      const modal = event.target.closest('[data-modal-root]');
      if (modal && event.target === modal) {
        closeModal(modal.id);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeTopModal();
    });
  }

  function initToasts() {
    function closeToast(toast) {
      if (!toast) return;
      toast.classList.add('hidden');
      toast.setAttribute('hidden', '');
    }

    document.addEventListener('click', (event) => {
      const closeBtn = event.target.closest('[data-toast-close]');
      if (!closeBtn) return;
      const toast = closeBtn.closest('[data-toast]');
      closeToast(toast);
    });

    document.querySelectorAll('[data-toast]').forEach((toast) => {
      const timeout = parseInt(toast.getAttribute('data-timeout') || '0', 10);
      if (timeout > 0) {
        setTimeout(() => closeToast(toast), timeout);
      }
    });
  }

  window.openModal = openModal;
  window.closeModal = closeModal;

  document.addEventListener('DOMContentLoaded', () => {
    initModalEvents();
    initToasts();
  });
})();
