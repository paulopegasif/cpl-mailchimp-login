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
    
    console.log('[CPL] Tentando fechar popup ID:', popupId);
    
    // Aguarda um pouco para garantir que o DOM está pronto
    setTimeout(function() {
      // Método 1: Clicar no botão de fechar
      var closeBtn = document.querySelector('.elementor-popup-modal .dialog-close-button');
      if (!closeBtn) {
        closeBtn = document.querySelector('[aria-label="Close"]');
      }
      
      if (closeBtn) {
        try {
          closeBtn.click();
          console.log('[CPL] Popup fechado via botão');
        } catch (e) {
          console.warn('[CPL] Erro ao clicar botão:', e);
        }
      }
      
      // Método 2: Usar API do Elementor se disponível
      if (window.elementorProFrontend && elementorProFrontend.modules && elementorProFrontend.modules.popup) {
        try {
          elementorProFrontend.modules.popup.closePopup({ id: popupId });
          console.log('[CPL] Popup fechado via API Elementor');
        } catch (e) {
          console.warn('[CPL] Erro API Elementor:', e.message);
        }
      }
      
      // Método 3: Ocultar manualmente o modal
      var modal = document.querySelector('.elementor-popup-modal');
      if (modal) {
        modal.style.display = 'none';
        console.log('[CPL] Modal ocultado manualmente');
      }
      
      // Remove overlay wrapper se existir
      var wrapper = document.querySelector('.elementor-popup-modal-wrapper');
      if (wrapper) {
        wrapper.style.display = 'none';
      }
      
    }, 200);
  }

  function lockGate() {
    console.log('[CPL] 🔒 Bloqueando acesso...');
    document.documentElement.classList.add('cpl-gate-open');
    document.documentElement.classList.remove('cpl-gate-unlocked');
    openPopup();
  }

  function unlockGate() {
    console.log('[CPL] 🔓 Liberando acesso...');
    
    var days = parseInt(CFG.cookieDays, 10);
    if (!days || days <= 0) days = 7;
    setCookie(COOKIE_NAME, '1', days);

    // Limpa classes de bloqueio
    document.documentElement.classList.remove('cpl-gate-open');
    document.body.style.overflow = 'auto';
    document.documentElement.style.overflow = 'auto';
    
    // Aguarda e depois adiciona a classe de sucesso
    setTimeout(function() {
      document.documentElement.classList.add('cpl-gate-unlocked');
      console.log('[CPL] Classe cpl-gate-unlocked adicionada');
    }, 50);
    
    // Fecha o popup
    closePopup();
    
    console.log('[CPL] ✅ Acesso liberado com sucesso!');
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
    
    // Verifica se é um form dentro do popup do Elementor
    var isPopupForm = form && (
      form.id === 'cpl-login-form' || 
      form.closest('.elementor-popup-modal') ||
      form.classList.contains('elementor-form')
    );
    
    if (!isPopupForm) return;

    console.log('[CPL] Formulário detectado, processando login...');

    // Bloqueia o Elementor de pegar o submit
    e.preventDefault();
    e.stopPropagation();
    if (typeof e.stopImmediatePropagation === 'function') {
      e.stopImmediatePropagation();
    }

    // Busca o campo de email de várias formas possíveis
    var emailInput = form.querySelector('input[type="email"]') || 
                     form.querySelector('input[name="email"]') ||
                     form.querySelector('input[name="form_fields[email]"]') ||
                     form.querySelector('[name*="email"]');
    
    var errorEl = document.getElementById('cpl-login-error') || 
                  form.querySelector('.cpl-error-message') ||
                  form.querySelector('.elementor-message');

    if (errorEl) {
      errorEl.textContent = '';
      errorEl.style.display = 'none';
    }

    var email = emailInput && emailInput.value ? emailInput.value.trim() : '';
    
    console.log('[CPL] Email encontrado:', email ? 'Sim' : 'Não', '- Valor:', email);
    
    if (!email) {
      console.warn('[CPL] Email vazio ou campo não encontrado');
      if (errorEl) {
        errorEl.textContent = 'Digite o e-mail que você usou na inscrição.';
        errorEl.style.display = 'block';
      } else {
        alert('Digite o e-mail que você usou na inscrição.');
      }
      return;
    }

    console.log('[CPL] Enviando requisição para:', CFG.restUrl);

    fetch(CFG.restUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email })
    })
    .then(function(res){
      console.log('[CPL] Resposta recebida - Status:', res.status);
      return res.json().then(function(data){
        return { ok: res.ok, data: data };
      });
    })
    .then(function(res){
      console.log('[CPL] Dados da resposta:', res.data);
      if (res.ok && res.data && res.data.ok) {
        console.log('[CPL] ✅ Login aprovado! Liberando acesso...');
        unlockGate();
      } else {
        var errorMsg = (res.data && res.data.error) || 'Não foi possível validar seu e-mail.';
        console.warn('[CPL] ❌ Login negado:', errorMsg);
        if (errorEl) {
          errorEl.textContent = errorMsg;
          errorEl.style.display = 'block';
        } else {
          alert(errorMsg);
        }
      }
    })
    .catch(function(err){
      console.error('[CPL] Erro na requisição:', err);
      var errorMsg = 'Falha de conexão. Tente novamente em instantes.';
      if (errorEl) {
        errorEl.textContent = errorMsg;
        errorEl.style.display = 'block';
      } else {
        alert(errorMsg);
      }
    });
  });

  // Se usuário fechar o popup com ESC e ainda não tiver cookie, reabre
  document.addEventListener('keyup', function(e){
    if (e.key === 'Escape' && !getCookie(COOKIE_NAME)) {
      setTimeout(lockGate, 2000);
    }
  });

})();