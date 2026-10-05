const loginSection = document.getElementById("login");
const registerSection = document.getElementById("register");
const logo = document.getElementById("logo");

document.querySelectorAll("[data-mode]").forEach((button) => {
  button.addEventListener("click", () => {
    const showRegister = button.dataset.mode === "register";

    document.getElementById("loginStatus").classList.add("hidden");
    document.getElementById("registerError").classList.add("hidden");
    document.getElementById("registerStatus").classList.add("hidden");
    loginSection.classList.toggle("hidden", showRegister);
    registerSection.classList.toggle("hidden", !showRegister);
    logo.classList.toggle("mb-[133px]", !showRegister);
    logo.classList.toggle("mb-[24px]", showRegister);

    const activeForm = showRegister
      ? document.getElementById("registerForm")
      : document.getElementById("loginForm");
    activeForm.querySelector("input").focus();
  });
});

document.getElementById("loginForm").addEventListener("submit", (event) => {
  event.preventDefault();
  document.getElementById("loginStatus").classList.remove("hidden");
});

document.getElementById("registerForm").addEventListener("submit", (event) => {
  event.preventDefault();

  const form = event.currentTarget;
  const formData = new FormData(form);
  const error = document.getElementById("registerError");
  const status = document.getElementById("registerStatus");
  const fail = (message) => {
    error.textContent = message;
    error.classList.remove("hidden");
    status.classList.add("hidden");
  };

  if (formData.get("password") !== formData.get("confirmPassword")) {
    fail("Wachtwoorden komen niet overeen.");
    return;
  }

  const day = Number(formData.get("day"));
  const month = Number(formData.get("month"));
  const year = Number(formData.get("year"));
  const birthDate = new Date(year, month - 1, day);

  if (
    birthDate.getFullYear() !== year ||
    birthDate.getMonth() !== month - 1 ||
    birthDate.getDate() !== day ||
    birthDate > new Date()
  ) {
    fail("Kies een geldige geboortedatum.");
    return;
  }

  error.classList.add("hidden");
  status.classList.remove("hidden");
});
