const loginSection = document.getElementById("login");
const registerSection = document.getElementById("register");
const logo = document.getElementById("logo");

document.querySelectorAll("[data-mode]").forEach((button) => {
  button.addEventListener("click", () => {
    const showRegister = button.dataset.mode === "register";

    ["loginStatus", "registerError", "registerStatus"].forEach((id) => {
      document.getElementById(id)?.classList.add("hidden");
    });
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
