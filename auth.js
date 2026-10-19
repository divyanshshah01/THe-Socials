/**
 * ============================================================
 * THE SOCIALS — Auth Form Handler
 * AJAX submission for register.html and login.html
 * ============================================================
 */

(function () {
  'use strict';

  /* ─────────────────────────────────────────────────────────────
     § UTILITIES
     ───────────────────────────────────────────────────────────── */

  /**
   * Display a toast message on the auth page
   */
  function showToast(type, message) {
    const toast = document.getElementById('auth-toast');
    if (!toast) return;

    toast.textContent = message;
    toast.className = 'auth-toast auth-toast--visible auth-toast--' + type;

    setTimeout(() => {
      toast.classList.remove('auth-toast--visible');
    }, 5000);
  }

  /**
   * Toggle loading state on a button
   */
  function setLoading(btn, loading) {
    if (!btn) return;
    if (loading) {
      btn.classList.add('auth-form__btn--loading');
      btn.disabled = true;
    } else {
      btn.classList.remove('auth-form__btn--loading');
      btn.disabled = false;
    }
  }


  /* ─────────────────────────────────────────────────────────────
     § REGISTRATION FORM HANDLER
     ───────────────────────────────────────────────────────────── */
  const registerForm = document.getElementById('register-form');

  if (registerForm) {
    const registerBtn = document.getElementById('register-btn');

    registerForm.addEventListener('submit', async function (e) {
      e.preventDefault();

      const fullName = document.getElementById('reg-name').value.trim();
      const email    = document.getElementById('reg-email').value.trim();
      const password = document.getElementById('reg-password').value;
      const joinCode = document.getElementById('reg-join-code').value.trim();

      // Client-side validation
      if (!fullName) { showToast('error', 'Please enter your full name.'); return; }
      if (!email)    { showToast('error', 'Please enter your email.'); return; }
      if (password.length < 8) { showToast('error', 'Password must be at least 8 characters.'); return; }

      setLoading(registerBtn, true);

      try {
        const response = await fetch('register.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            full_name: fullName,
            email: email,
            password: password,
            join_code: joinCode,
          }),
        });

        const text = await response.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (jsonErr) {
          console.error('Registration server response is not valid JSON:', text);
          showToast('error', 'Server returned invalid format. Check console.');
          return;
        }

        if (data.status === 'success') {
          showToast('success', data.message);
          registerForm.reset();

          // Redirect to login after brief delay
          setTimeout(() => {
            window.location.href = 'login.html';
          }, 1800);
        } else {
          showToast('error', data.message || data.error || 'Registration failed.');
        }
      } catch (err) {
        console.error('Registration failed:', err);
        showToast('error', 'Connection error: ' + err.message);
      } finally {
        setLoading(registerBtn, false);
      }
    });
  }


  /* ─────────────────────────────────────────────────────────────
     § LOGIN FORM HANDLER
     ───────────────────────────────────────────────────────────── */
  const loginForm = document.getElementById('login-form');

  if (loginForm) {
    const loginBtn = document.getElementById('login-btn');

    loginForm.addEventListener('submit', async function (e) {
      e.preventDefault();

      const email    = document.getElementById('login-email').value.trim();
      const password = document.getElementById('login-password').value;

      // Client-side validation
      if (!email)    { showToast('error', 'Please enter your email.'); return; }
      if (!password) { showToast('error', 'Please enter your password.'); return; }

      setLoading(loginBtn, true);

      try {
        const response = await fetch('login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            email: email,
            password: password,
          }),
        });

        const text = await response.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (jsonErr) {
          console.error('Login server response is not valid JSON:', text);
          showToast('error', 'Server returned invalid format. Check console.');
          return;
        }

        if (data.status === 'success') {
          showToast('success', data.message);

          // Redirect to dashboard
          setTimeout(() => {
            window.location.href = data.redirect || 'dashboard.html';
          }, 1200);
        } else {
          showToast('error', data.message || data.error || 'Login failed.');
        }
      } catch (err) {
        console.error('Login failed:', err);
        showToast('error', 'Connection error: ' + err.message);
      } finally {
        setLoading(loginBtn, false);
      }
    });
  }

})();
