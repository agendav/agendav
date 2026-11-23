/*
 * Preferences page: saves the form through the JSON endpoint
 * POST /preferences and returns to the calendar on success.
 *
 * Plain JavaScript, no dependencies. Loaded only on the preferences page.
 */
document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('prefs_form');
  var errorBox = document.getElementById('prefs_error');

  var showError = function(message) {
    errorBox.textContent = message;
    errorBox.hidden = false;
    errorBox.scrollIntoView();
  };

  form.addEventListener('submit', function(event) {
    event.preventDefault();
    errorBox.hidden = true;

    var data = {};
    new FormData(form).forEach(function(value, name) {
      if (name !== '_token') {
        data[name] = value;
      }
    });

    fetch(form.action, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': form.elements['_token'].value,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(data)
    }).then(function(response) {
      // Session expired: reload, the server redirects to the login page
      if (response.status === 401) {
        window.location.reload();
        return null;
      }
      return response.json();
    }).then(function(result) {
      if (result === null) {
        return;
      }
      if (result.result === 'SUCCESS') {
        window.location.href = form.dataset.returnUrl;
        return;
      }
      showError(result.message);
    }).catch(function() {
      showError(form.dataset.errorMessage);
    });
  });
});
