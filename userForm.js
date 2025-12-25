document.addEventListener("DOMContentLoaded", function () {
  const passwordInput = document.getElementById("password");
  const confirmInput = document.getElementById("confirm_password");
  const matchDiv = document.getElementById("password-match");
  
  confirmInput.addEventListener("input", function () {
    const password = passwordInput.value;
    const confirmPassword = this.value;

    if (confirmPassword === "") {
      matchDiv.textContent = "";
      matchDiv.className = "";
    } else if (password === confirmPassword) {
      matchDiv.textContent = "✓ Passwords match";
      matchDiv.className = "text-green-600";
    } else {
      matchDiv.textContent = "✗ Passwords do not match";
      matchDiv.className = "text-red-600";
    }
  });

  
  document.getElementById("userForm").addEventListener("submit", function (e) {
    const password = passwordInput.value;
    const confirm = confirmInput.value;

    if (password.length < 8) {
      e.preventDefault();
      alert("Password must be at least 8 characters long");
      passwordInput.focus();
      return false;
    }

    if (!/[A-Z]/.test(password)) {
      e.preventDefault();
      alert("Password must contain at least one uppercase letter");
      passwordInput.focus();
      return false;
    }

    if (!/[a-z]/.test(password)) {
      e.preventDefault();
      alert("Password must contain at least one lowercase letter");
      passwordInput.focus();
      return false;
    }

    if (!/[0-9]/.test(password)) {
      e.preventDefault();
      alert("Password must contain at least one number");
      passwordInput.focus();
      return false;
    }

    if (password !== confirm) {
      e.preventDefault();
      alert("Passwords do not match");
      confirmInput.focus();
      return false;
    }

    return true;
  });

  const roleSelect = document.getElementById("role");
  roleSelect.addEventListener("change", function () {
    const adminIndicator = document.querySelector(".role-indicator.role-admin");
    const memberIndicator = document.querySelector(
      ".role-indicator.role-member"
    );

    if (this.value === "Admin") {
      adminIndicator.style.opacity = "1";
      adminIndicator.style.fontWeight = "bold";
      memberIndicator.style.opacity = "0.6";
      memberIndicator.style.fontWeight = "normal";
    } else {
      memberIndicator.style.opacity = "1";
      memberIndicator.style.fontWeight = "bold";
      adminIndicator.style.opacity = "0.6";
      adminIndicator.style.fontWeight = "normal";
    }
  });

  const form = document.getElementById("userForm");
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => (alert.style.display = "none"));

  if (form) {
    form.reset();

    form.querySelectorAll("input, select").forEach((field) => {
      if (
        field.type !== "submit" &&
        field.type !== "button" &&
        field.type !== "reset"
      ) {
        field.value = "";
      }
    });


    const matchMsg = document.getElementById("password-match");
    if (matchMsg) matchMsg.textContent = "";
  }

  form.addEventListener("reset", () => {
    setTimeout(() => {
      form.querySelectorAll("input, select").forEach((field) => {
        if (
          field.type !== "submit" &&
          field.type !== "button" &&
          field.type !== "reset"
        ) {
          field.value = "";
        }
      });

      const matchMsg = document.getElementById("password-match");
      if (matchMsg) matchMsg.textContent = "";

      const alerts = document.querySelectorAll(".alert");
      alerts.forEach((alert) => (alert.style.display = "none"));
    }, 0);
  });
  roleSelect.dispatchEvent(new Event("change"));
});
