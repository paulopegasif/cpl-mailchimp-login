# CPL Mailchimp Login — WordPress Plugin

Validação de acesso a páginas protegidas utilizando Mailchimp (Audience + Tag).  
Ideal para páginas de aulas (CPL), pré-MBAs, workshops e jornadas.

---

## ✨ Visão Geral

O **CPL Mailchimp Login** permite que você proteja páginas específicas do WordPress, liberando o acesso apenas para usuários cujo **e-mail esteja cadastrado na Audience do Mailchimp** e possua a **Tag obrigatória** configurada.

Funciona como um “gate” leve, sem criar usuários no WordPress, usando apenas e-mail + Mailchimp como fonte de verdade.

---

# 🚀 Versão 2.0 (Atual)

### Principais recursos

- 🔐 **Proteção por página** via checkbox (“Proteger esta página”)  
- 🧩 **Integração com popup do Elementor**  
- ⚡ **Validação em tempo real via REST API**  
- 🍪 **Acesso persistente** via cookie configurável  
- 🌙 **Overlay automático**  
- ⚙️ **Painel completo no WordPress**  
- 🔄 **Reutilizável para qualquer lançamento**

---

## 🏗 Como funciona (versão 2.0)

1. Usuário acessa a página protegida.  
2. Overlay bloqueia o conteúdo.  
3. O popup do Elementor abre automaticamente.  
4. O usuário envia o e-mail pelo formulário.  
5. O plugin consulta o Mailchimp (status + tag).  
6. Se aprovado → Libera acesso + grava cookie.

---

# 🔥 Versão 1.0 (Legacy)

- Gate manual com necessidade de colar JS/CSS/HTML na página.  
- Configurações básicas apenas.  
- Sem integração com Elementor.  
- Útil para fluxos simples.

---

# 📄 Instalação

1. Enviar o ZIP em **Plugins → Adicionar novo → Enviar plugin**  
2. Configurar em **Configurações → CPL Mailchimp Login**  
3. Criar popup no Elementor  
4. Editar página e ativar:  
   **“Proteger esta página com CPL Mailchimp Login”**

---

# 📬 Endpoint

`POST /wp-json/cpl/v1/login`

Body:
```json
{ "email": "usuario@example.com" }
```

---

# 🧑‍💻 Autor

Desenvolvido por Paulo Silva, para o fluxo de CPLs e pré-lançamentos.
