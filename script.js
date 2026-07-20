const form = document.querySelector('#registrationForm');
const cpfInput = document.querySelector('#cpf');
const phoneInput = document.querySelector('#telefone');
const alertBox = document.querySelector('#formAlert');
const submitButton = document.querySelector('#submitButton');

const digits = (value) => value.replace(/\D/g, '');

cpfInput.addEventListener('input', () => {
  const value = digits(cpfInput.value).slice(0, 11);
  cpfInput.value = value
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d)/, '$1.$2')
    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
});

phoneInput.addEventListener('input', () => {
  const value = digits(phoneInput.value).slice(0, 11);
  phoneInput.value = value.length > 10
    ? value.replace(/(\d{2})(\d{5})(\d{1,4})/, '($1) $2-$3')
    : value.replace(/(\d{2})(\d{4})(\d{1,4})/, '($1) $2-$3');
});

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  alertBox.hidden = true;

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  submitButton.disabled = true;
  submitButton.textContent = 'Enviando...';

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { Accept: 'application/json' },
    });
    const data = await response.json();

    alertBox.textContent = data.message;
    alertBox.className = `alert ${data.success ? 'success' : 'error'}`;
    alertBox.hidden = false;

    if (data.success) {
      form.reset();
      submitButton.textContent = 'Inscrição confirmada ✓';
      return;
    }
  } catch {
    alertBox.textContent = 'Erro de comunicação com o servidor. Tente novamente.';
    alertBox.className = 'alert error';
    alertBox.hidden = false;
  }

  submitButton.disabled = false;
  submitButton.textContent = 'Confirmar inscrição';
});
