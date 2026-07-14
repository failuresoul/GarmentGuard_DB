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

// Perform overall validation on a form
function validateForm(formEl) {
  let isValid = true;
  clearErrors(formEl);

  const inputs = formEl.querySelectorAll('input, select, textarea');
  inputs.forEach(input => {
    const val = input.value.trim();
    const isRequired = input.getAttribute('data-required') === 'true';
    let hasError = false;
    let errorMsg = '';

    if (isRequired && val === '') {
      hasError = true;
      errorMsg = 'This field is required';
    } else if (val !== '') {
      // Email fields: test standard email regex
      if (input.type === 'email' || input.name === 'email' || input.id === 'email') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(val)) {
          hasError = true;
          errorMsg = 'Invalid email address';
        }
      }
      // Number range check if data-min / data-max are present
      const hasMin = input.hasAttribute('data-min');
      const hasMax = input.hasAttribute('data-max');
      if ((hasMin || hasMax) && !hasError) {
        const numVal = parseFloat(val);
        if (isNaN(numVal)) {
          hasError = true;
          errorMsg = 'Must be a valid number';
        } else {
          if (hasMin) {
            const min = parseFloat(input.getAttribute('data-min'));
            if (numVal < min) {
              hasError = true;
              errorMsg = `Value must be at least ${min}`;
            }
          }
          if (hasMax && !hasError) {
            const max = parseFloat(input.getAttribute('data-max'));
            if (numVal > max) {
              hasError = true;
              errorMsg = `Value must be at most ${max}`;
            }
          }
        }
      }
    }

    if (hasError) {
      isValid = false;
      input.style.borderColor = '#E24B4A';
      input.classList.add('is-invalid');

      // Find or create error span right below the input
      let errorSpan = input.parentNode.querySelector('.error-message');
      if (!errorSpan) {
        errorSpan = document.createElement('span');
        errorSpan.className = 'error-message';
        errorSpan.style.color = '#E24B4A';
        errorSpan.style.fontSize = '12px';
        errorSpan.style.display = 'block';
        errorSpan.style.marginTop = '4px';
        
        // Insert it right after the input
        input.parentNode.insertBefore(errorSpan, input.nextSibling);
      }
      errorSpan.innerText = errorMsg;
    } else {
      input.classList.add('is-valid');
    }
  });

  return { valid: isValid };
}

// Clear all form validation borders and error spans
function clearErrors(formEl) {
  const inputs = formEl.querySelectorAll('input, select, textarea');
  inputs.forEach(input => {
    input.style.borderColor = '';
    input.classList.remove('is-invalid', 'is-valid');

    const errorSpan = input.parentNode.querySelector('.error-message');
    if (errorSpan) {
      errorSpan.remove();
    }
    
    // Also remove native feedback if validateInput created it
    const feedback = input.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
      feedback.remove();
    }
  });
}
