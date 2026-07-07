/* ============================================================
   auth.js — Login & Register client-side logic
   ============================================================ */
"use strict";

document.addEventListener("DOMContentLoaded", () => {
  initPasswordToggle();
  initPasswordStrength();
  initRegisterValidation();
  initLoginValidation();
  initAvatarPreview();
});

// ============================================================
// Password show/hide toggles
// ============================================================
function initPasswordToggle() {
  document.querySelectorAll("[data-toggle-password]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetId = btn.dataset.togglePassword;
      const input = document.getElementById(targetId);
      if (!input) return;
      const isPass = input.type === "password";
      input.type = isPass ? "text" : "password";
      btn.querySelector("i").className = isPass
        ? "ri-eye-off-line"
        : "ri-eye-line";
    });
  });
}

// ============================================================
// Password strength indicator
// ============================================================
function initPasswordStrength() {
  const passwordInput = document.getElementById("password");
  const strengthBar = document.getElementById("strengthFill");
  const strengthText = document.getElementById("strengthText");
  if (!passwordInput || !strengthBar) return;

  passwordInput.addEventListener("input", () => {
    const val = passwordInput.value;
    const score = getPasswordScore(val);
    const levels = [
      { pct: 0, color: "#e2e8f0", label: "" },
      { pct: 25, color: "#ef4444", label: "Weak" },
      { pct: 50, color: "#f59e0b", label: "Fair" },
      { pct: 75, color: "#3b82f6", label: "Good" },
      { pct: 100, color: "#10b981", label: "Strong" },
    ];
    const level = levels[score];
    strengthBar.style.width = level.pct + "%";
    strengthBar.style.background = level.color;
    if (strengthText) {
      strengthText.textContent = level.label;
      strengthText.style.color = level.color;
    }
  });
}
function getPasswordScore(pwd) {
  let score = 0;
  if (pwd.length >= 8) score++;
  if (/[A-Z]/.test(pwd)) score++;
  if (/[0-9]/.test(pwd)) score++;
  if (/[^A-Za-z0-9]/.test(pwd)) score++;
  return score;
}

// ============================================================
// Register form validation
// ============================================================
function initRegisterValidation() {
  const form = document.getElementById("registerForm");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!validateRegisterForm(form)) return;

    const btn = form.querySelector('button[type="submit"]');
    setLoading(btn, true);

    const formData = new FormData(form);
    try {
      const res = await fetch(window.BASE_URL + "/api/auth.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (data.success) {
        showToast("Account created! Redirecting...", "success");
        setTimeout(
          () =>
            (window.location.href =
              data.redirect || window.BASE_URL + "/pages/login.php"),
          1200,
        );
      } else {
        showToast(data.message || "Registration failed.", "error");
        setLoading(btn, false);
        if (data.field) {
          markInvalid(document.getElementById(data.field), data.message);
        }
      }
    } catch {
      showToast("Network error. Please try again.", "error");
      setLoading(btn, false);
    }
  });

  // Real-time validation
  form.querySelectorAll(".form-control").forEach((input) => {
    input.addEventListener("blur", () => validateField(input));
    input.addEventListener("input", () => clearError(input));
  });
}

function validateRegisterForm(form) {
  let valid = true;
  const fields = {
    full_name: { min: 3, label: "Full name" },
    student_id: { min: 5, label: "Student ID" },
    email: { email: true, label: "Email" },
    password: { min: 6, label: "Password" },
    confirm_password: { match: "password", label: "Confirm password" },
    department_id: { required: true, label: "Department" },
    batch: { required: true, label: "Batch" },
  };
  for (const [id, rules] of Object.entries(fields)) {
    const el = form.querySelector(`[name="${id}"]`);
    if (!el) continue;
    const val = el.value.trim();
    if (rules.required && !val) {
      markInvalid(el, `${rules.label} is required.`);
      valid = false;
      continue;
    }
    if (rules.min && val.length < rules.min) {
      markInvalid(
        el,
        `${rules.label} must be at least ${rules.min} characters.`,
      );
      valid = false;
      continue;
    }
    if (rules.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
      markInvalid(el, "Enter a valid email address.");
      valid = false;
      continue;
    }
    if (rules.match) {
      const other = form.querySelector(`[name="${rules.match}"]`);
      if (other && val !== other.value) {
        markInvalid(el, "Passwords do not match.");
        valid = false;
        continue;
      }
    }
    markValid(el);
  }
  return valid;
}

function validateField(input) {
  const val = input.value.trim();
  const name = input.name;
  if (!val && input.required) {
    markInvalid(input, "This field is required.");
    return;
  }
  if (name === "email" && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
    markInvalid(input, "Enter a valid email.");
    return;
  }
  if (val) markValid(input);
}

// ============================================================
// Login form validation
// ============================================================
function initLoginValidation() {
  const form = document.getElementById("loginForm");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = form.querySelector('[name="email"]').value.trim();
    const password = form.querySelector('[name="password"]').value;
    if (!email || !password) {
      showToast("Please fill in all fields.", "warning");
      return;
    }

    const btn = form.querySelector('button[type="submit"]');
    setLoading(btn, true);

    const formData = new FormData(form);
    formData.append("action", "login");
    try {
      const res = await fetch(window.BASE_URL + "/api/auth.php", {
        method: "POST",
        body: formData,
      });
      const data = await res.json();
      if (data.success) {
        showToast("Welcome back!", "success");
        setTimeout(() => (window.location.href = data.redirect), 1000);
      } else {
        showToast(data.message || "Invalid credentials.", "error");
        setLoading(btn, false);
      }
    } catch {
      showToast("Network error.", "error");
      setLoading(btn, false);
    }
  });
}

// ============================================================
// Avatar preview
// ============================================================
function initAvatarPreview() {
  const input = document.getElementById("profilePicInput");
  const preview = document.getElementById("avatarPreview");
  if (!input || !preview) return;

  input.addEventListener("change", () => {
    const file = input.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      showToast("Image must be under 5MB.", "warning");
      return;
    }
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });
}

// ============================================================
// Helpers
// ============================================================
function markInvalid(el, msg) {
  if (!el) return;
  el.classList.add("is-invalid");
  el.classList.remove("is-valid");
  const err =
    el.parentElement.querySelector(".form-error") ||
    el.closest(".form-group")?.querySelector(".form-error");
  if (err) {
    err.textContent = msg;
    err.style.display = "block";
  }
}
function markValid(el) {
  el.classList.remove("is-invalid");
  el.classList.add("is-valid");
  const err =
    el.parentElement.querySelector(".form-error") ||
    el.closest(".form-group")?.querySelector(".form-error");
  if (err) err.style.display = "none";
}
function clearError(el) {
  el.classList.remove("is-invalid");
  const err =
    el.parentElement.querySelector(".form-error") ||
    el.closest(".form-group")?.querySelector(".form-error");
  if (err) err.style.display = "none";
}
function setLoading(btn, loading) {
  if (!btn) return;
  btn.classList.toggle("is-loading", loading);
  btn.disabled = loading;
}
