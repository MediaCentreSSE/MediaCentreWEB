try {
  const email = document.getElementById('mce-EMAIL');
  const error = document.getElementById("error");
  const submitButton = document.getElementById('mc-embedded-subscribe');
  submitButton.disabled = true;

  const setMessage = (message) => {
    error.innerHTML = message;
    return false;
  };

  const validateEmailInput = (onValue) => {
    const messages = {
      lookingGood: 'This one is looking good!',
      typeMismatch: 'I am expecting an e-mail address!',
      valueMissing: 'You need to enter an e-mail address',
      tooShort: `Email should be at least ${email.minLength} characters; you entered ${email.value.length}`
    };

    if(!onValue.validity.valid) {
      (onValue.validity.typeMismatch) ? setMessage(messages.typeMismatch) : true;
      (onValue.validity.valueMissing) ? setMessage(messages.valueMissing) : true;
      (onValue.validity.tooShort)     ? setMessage(messages.tooShort)     : true;
      submitButton.disabled = true;
    }
    else {
      setMessage(messages.lookingGood);
      submitButton.disabled = false;
    }
    return false;
  };

  email.addEventListener('input', (event) => {
    validateEmailInput(email);
  });
}
catch (e) { }
