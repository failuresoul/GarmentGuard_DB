function validateInput(inputElement, validationFn, errorMessage) {
  const value = inputElement.value.trim();
  const isValid = validationFn(value);
  const parent = inputElement.closest('.form-group') || inputElement.parentElement;
  let feedback = parent.querySelector('.invalid-feedback');
  if (!isValid) {
    inputElement.classList.add('is-invalid');
    inputElement.classList.remove('is-valid');
    if (!feedback) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback';
      feedback.style.color = '#E24B4A';
      feedback.style.fontSize = '12px';
      feedback.style.marginTop = '4px';
      parent.appendChild(feedback);
    }
    feedback.innerText = errorMessage;
  } else {
    inputElement.classList.remove('is-invalid');
    inputElement.classList.add('is-valid');
    if (feedback) {
      feedback.remove();
    }
  }
  return isValid;
}

function isNotEmpty(value) {
  return value.length > 0;
}

function isValidEmail(value) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(value);
}

function isValidPhone(value) {
  const re = /^[0-9+-\s]{8,15}$/;
  return re.test(value);
}
