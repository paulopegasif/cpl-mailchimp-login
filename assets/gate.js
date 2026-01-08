(function(){
  var CFG = window.CPLMC_FRONT || {};
  var COOKIE_NAME = 'cpl_unlocked';

  function setCookie(name, value, days) {
    var d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = name + '=' + (value || '') + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
  }

  function getCookie(name) {
    var nameEQ = name + '=';
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i].trim();
      if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length);
    }
    return null;
  }

  function openPopup() {
    var popupId = CFG.popupId;
    if (!popupId) return;

    if (window.elementorProFrontend && elementorProFrontend.modules && elementorProFrontend.modules.popup) {
      try {
        elementorProFrontend.modules.popup.showPopup({ id: popupId });
      } catch (e) {}
    } else {
      // tenta novamente depois
      setTimeout(openPopup, 500);
    }
  }

  function closePopup() {
    var popupId = CFG.popupId;
    if (!popupId) return;

    if (window.elementorProFrontend && elementorProFrontend.modules && elementorProFrontend.modules.popup) {
      try {
        elementorProFrontend.modules.popup.closePopup({ id: popupId });
      } catch (e) {}
    }
  }

  function lockGate() {
    document.documentElement.classList.add('cpl-gate-open');
    document.documentElement.classList.remove('cpl-gate-unlocked');
    openPopup();
  }

  function unlockGate() {
    var days = parseInt(CFG.cookieDays, 10);
    if (!days || days <= 0) days = 7;
    setCookie(COOKIE_NAME, '1', days);

    document.documentElement.classList.remove('cpl-gate-open');
    document.documentElement.classList.add('cpl-gate-unlocked');
    closePopup();
  }

  // Bypass via parametro ?cpl_access=1 (ex.: link enviado no e-mail)
  try {
    var params = new URLSearchParams(window.location.search);
    if (params.get('cpl_access') === '1') {
      unlockGate();
      if (history.replaceState) {
        params.delete('cpl_access');
        var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState({}, '', newUrl);
      }
      return; // já liberado
    }
  } catch (e) {}

  // Se já tem cookie, libera diretamente
  if (getCookie(COOKIE_NAME) === '1') {
    document.documentElement.classList.add('cpl-gate-unlocked');
  } else {
    lockGate();
  }

  // Handler do formulário de login dentro do popup
  document.addEventListener('submit', function(e){
    var form = e.target;
    if (!form || form.id !== 'cpl-login-form') return;

    // Bloqueia o Elementor de pegar o submit
    e.preventDefault();
    e.stopPropagation();
    if (typeof e.stopImmediatePropagation === 'function') {
      e.stopImmediatePropagation();
    }

    var emailInput = form.querySelector('input[type="email"], input[name="email"]');
    var errorEl = document.getElementById('cpl-login-error');

    if (errorEl) errorEl.textContent = '';

    var email = emailInput && emailInput.value ? emailInput.value.trim() : '';
    if (!email) {
      if (errorEl) errorEl.textContent = 'Digite o e-mail que você usou na inscrição.';
      return;
    }

    fetch(CFG.restUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email })
    })
    .then(function(res){
      return res.json().then(function(data){
        return { ok: res.ok, data: data };
      });
    })
    .then(function(res){
      if (res.ok && res.data && res.data.ok) {
        unlockGate();
      } else {
        if (errorEl) errorEl.textContent = (res.data && res.data.error) || 'Não foi possível validar seu e-mail.';
      }
    })
    .catch(function(){
      if (errorEl) errorEl.textContent = 'Falha de conexão. Tente novamente em instantes.';
    });
  });

  // Se usuário fechar o popup com ESC e ainda não tiver cookie, reabre
  document.addEventListener('keyup', function(e){
    if (e.key === 'Escape' && !getCookie(COOKIE_NAME)) {
      setTimeout(lockGate, 2000);
    }
  });

})();