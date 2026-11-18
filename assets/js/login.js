const loginSection = document.getElementById("login");
const registerSection = document.getElementById("register");
const authPanelSwitcher = document.getElementById("authPanelSwitcher");
const loginForm = document.getElementById("loginForm");
const registerForm = document.getElementById("registerForm");

const birthdateSelects = Array.from(
  document.querySelectorAll("#register [data-birthdate-selects] select"),
);

const authValidationPopover = document.createElement("div");
authValidationPopover.id = "authValidationPopover";
authValidationPopover.setAttribute("role", "alert");
authValidationPopover.setAttribute("aria-live", "assertive");
authValidationPopover.setAttribute("aria-hidden", "true");
const authValidationIcon = document.createElement("span");
authValidationIcon.className = "auth-validation-icon";
authValidationIcon.setAttribute("aria-hidden", "true");
authValidationIcon.textContent = "!";
const authValidationMessage = document.createElement("span");
authValidationPopover.append(authValidationIcon, authValidationMessage);
document.body.append(authValidationPopover);

let activeInvalidControl = null;
const hideAuthValidation = () => {
  authValidationPopover.classList.remove("is-visible");
  authValidationPopover.setAttribute("aria-hidden", "true");
  if (activeInvalidControl) {
    activeInvalidControl.removeAttribute("aria-invalid");
    const descriptions = (activeInvalidControl.getAttribute("aria-describedby") || "")
      .split(/\s+/)
      .filter((id) => id && id !== authValidationPopover.id);
    if (descriptions.length > 0) {
      activeInvalidControl.setAttribute("aria-describedby", descriptions.join(" "));
    } else {
      activeInvalidControl.removeAttribute("aria-describedby");
    }
    activeInvalidControl = null;
  }
};

const positionAuthValidation = (control) => {
  const controlBounds = control.getBoundingClientRect();
  const popoverBounds = authValidationPopover.getBoundingClientRect();
  const left = Math.max(
    12,
    Math.min(controlBounds.left, window.innerWidth - popoverBounds.width - 12),
  );
  const top =
    controlBounds.bottom + popoverBounds.height + 8 <= window.innerHeight - 12
      ? controlBounds.bottom + 8
      : Math.max(12, controlBounds.top - popoverBounds.height - 8);
  authValidationPopover.style.left = `${left}px`;
  authValidationPopover.style.top = `${top}px`;
};

const showAuthValidation = (control, message) => {
  hideAuthValidation();
  activeInvalidControl = control;
  control.setAttribute("aria-invalid", "true");
  const descriptions = (control.getAttribute("aria-describedby") || "")
    .split(/\s+/)
    .filter(Boolean);
  if (!descriptions.includes(authValidationPopover.id)) {
    descriptions.push(authValidationPopover.id);
  }
  control.setAttribute("aria-describedby", descriptions.join(" "));
  authValidationMessage.textContent = message;
  authValidationPopover.setAttribute("aria-hidden", "false");
  positionAuthValidation(control);
  authValidationPopover.classList.remove("is-visible");
  void authValidationPopover.offsetWidth;
  authValidationPopover.classList.add("is-visible");
  control.focus();
};

if (birthdateSelects.length > 0) {
  const closeBirthdateMenus = (except = null) => {
    document.querySelectorAll(".birthdate-select.is-open").forEach((wrapper) => {
      if (wrapper !== except) {
        wrapper.classList.remove("is-open");
        wrapper.querySelector(".birthdate-trigger").setAttribute("aria-expanded", "false");
        wrapper.querySelector(".birthdate-menu").setAttribute("aria-hidden", "true");
      }
    });
  };

  birthdateSelects.forEach((select) => {
    const placeholder = select.dataset.placeholder;
    const wrapper = document.createElement("div");
    wrapper.className = "birthdate-select";
    wrapper.dataset.selectId = select.id;

    const trigger = document.createElement("button");
    trigger.type = "button";
    trigger.className = "birthdate-trigger";
    trigger.id = `${select.id}Trigger`;
    trigger.setAttribute("role", "combobox");
    trigger.setAttribute("aria-label", placeholder);
    trigger.setAttribute("aria-haspopup", "listbox");
    trigger.setAttribute("aria-expanded", "false");
    trigger.setAttribute("aria-controls", `${select.id}Options`);
    trigger.setAttribute("aria-required", "true");

    const value = document.createElement("span");
    value.className = "birthdate-value";
    trigger.append(value);
    const chevron = document.createElement("span");
    chevron.className = "birthdate-chevron";
    chevron.setAttribute("aria-hidden", "true");
    trigger.append(chevron);

    const menu = document.createElement("div");
    menu.id = `${select.id}Options`;
    menu.className = "birthdate-menu";
    menu.setAttribute("role", "listbox");
    menu.setAttribute("aria-label", placeholder);
    menu.setAttribute("aria-hidden", "true");

    const choices = Array.from(select.options).filter(
      (option) => !option.disabled && option.value !== "",
    );
    const optionElements = choices.map((choice, index) => {
      const option = document.createElement("div");
      option.id = `${select.id}Option${index}`;
      option.className = "birthdate-option";
      option.setAttribute("role", "option");
      option.setAttribute("aria-selected", String(choice.selected));
      option.textContent = choice.textContent;
      menu.append(option);
      return option;
    });

    let activeIndex = choices.findIndex((choice) => choice.selected);
    let typeahead = "";
    let typeaheadTimer;

    const updateValue = () => {
      const selectedOption = select.options[select.selectedIndex];
      const selectedLabel = select.value ? selectedOption.textContent : placeholder;
      value.textContent = selectedLabel;
      trigger.setAttribute(
        "aria-label",
        select.value ? `${placeholder}: ${selectedLabel}` : placeholder,
      );
      optionElements.forEach((option, index) => {
        option.setAttribute("aria-selected", String(choices[index].value === select.value));
      });
    };

    const setActiveOption = (index) => {
      activeIndex = Math.max(0, Math.min(index, choices.length - 1));
      optionElements.forEach((option, optionIndex) => {
        option.classList.toggle("is-active", optionIndex === activeIndex);
      });
      trigger.setAttribute("aria-activedescendant", optionElements[activeIndex].id);
      optionElements[activeIndex].scrollIntoView({ block: "nearest" });
    };

    const setOpen = (isOpen) => {
      if (isOpen) closeBirthdateMenus(wrapper);
      wrapper.classList.toggle("is-open", isOpen);
      trigger.setAttribute("aria-expanded", String(isOpen));
      menu.setAttribute("aria-hidden", String(!isOpen));
      if (isOpen) {
        const selectedIndex = choices.findIndex((choice) => choice.value === select.value);
        setActiveOption(selectedIndex >= 0 ? selectedIndex : 0);
      }
    };

    const selectChoice = (index) => {
      select.value = choices[index].value;
      select.dispatchEvent(new Event("change", { bubbles: true }));
      updateValue();
      if (activeInvalidControl === trigger) hideAuthValidation();
      trigger.setAttribute("aria-invalid", "false");
      setOpen(false);
      trigger.focus();
    };

    trigger.addEventListener("click", () => {
      setOpen(trigger.getAttribute("aria-expanded") !== "true");
    });

    trigger.addEventListener("keydown", (event) => {
      const isOpen = trigger.getAttribute("aria-expanded") === "true";
      if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        event.preventDefault();
        if (!isOpen) {
          setOpen(true);
        } else {
          setActiveOption(activeIndex + (event.key === "ArrowDown" ? 1 : -1));
        }
      } else if (event.key === "Home" && isOpen) {
        event.preventDefault();
        setActiveOption(0);
      } else if (event.key === "End" && isOpen) {
        event.preventDefault();
        setActiveOption(choices.length - 1);
      } else if ((event.key === "Enter" || event.key === " ") && isOpen) {
        event.preventDefault();
        selectChoice(activeIndex);
      } else if (event.key === "Escape" && isOpen) {
        event.preventDefault();
        setOpen(false);
      } else if (event.key === "Tab" && isOpen) {
        setOpen(false);
      } else if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
        typeahead += event.key.toLocaleLowerCase();
        window.clearTimeout(typeaheadTimer);
        typeaheadTimer = window.setTimeout(() => {
          typeahead = "";
        }, 700);
        const matchIndex = choices.findIndex((choice) =>
          choice.textContent.toLocaleLowerCase().startsWith(typeahead),
        );
        if (matchIndex >= 0) {
          if (!isOpen) setOpen(true);
          setActiveOption(matchIndex);
        }
      }
    });

    menu.addEventListener("click", (event) => {
      const optionIndex = optionElements.indexOf(event.target.closest('[role="option"]'));
      if (optionIndex >= 0) selectChoice(optionIndex);
    });

    select.addEventListener("change", updateValue);
    select.required = false;
    select.tabIndex = -1;
    select.hidden = true;
    select.setAttribute("aria-hidden", "true");
    select.parentNode.insertBefore(wrapper, select);
    wrapper.append(trigger, select, menu);
    updateValue();
  });

  document.addEventListener("click", (event) => {
    if (!event.target.closest("[data-birthdate-selects]")) closeBirthdateMenus();
  });

}

const authForms = [loginForm, registerForm].filter(Boolean);
authForms.forEach((form) => {
  form.noValidate = true;
  form.addEventListener("submit", (event) => {
    const invalidControl = Array.from(
      form.querySelectorAll('input:not([type="hidden"]), select:not([hidden]), textarea'),
    ).find((control) => !control.checkValidity());

    if (invalidControl) {
      event.preventDefault();
      const message = invalidControl.validity.valueMissing
        ? "Vul dit veld in."
        : invalidControl.validity.typeMismatch
          ? "Vul een geldig e-mailadres in."
          : invalidControl.validationMessage;
      showAuthValidation(invalidControl, message);
      return;
    }

    const missingBirthdate = form === registerForm
      ? birthdateSelects.find((select) => !select.value)
      : null;
    if (missingBirthdate) {
      event.preventDefault();
      showAuthValidation(missingBirthdate.previousElementSibling, "Vul dit veld in.");
      return;
    }

    hideAuthValidation();
  });

  form.addEventListener("input", (event) => {
    if (event.target === activeInvalidControl) hideAuthValidation();
  });
  form.addEventListener("change", (event) => {
    if (event.target === activeInvalidControl) hideAuthValidation();
  });
  form.addEventListener("focusin", (event) => {
    if (activeInvalidControl && event.target !== activeInvalidControl) hideAuthValidation();
  });
});

window.addEventListener("resize", () => {
  if (activeInvalidControl) positionAuthValidation(activeInvalidControl);
});
window.addEventListener("scroll", () => {
  if (activeInvalidControl) positionAuthValidation(activeInvalidControl);
}, true);

document.querySelectorAll("[data-mode]").forEach((button) => {
  button.addEventListener("click", () => {
    const showRegister = button.dataset.mode === "register";
    hideAuthValidation();
    const incomingSection = showRegister ? registerSection : loginSection;
    const outgoingSection = showRegister ? loginSection : registerSection;

    if (
      incomingSection.classList.contains("hidden") === false ||
      authPanelSwitcher.classList.contains("is-transitioning")
    ) {
      return;
    }

    ["loginStatus", "registerError", "registerStatus"].forEach((id) => {
      document.getElementById(id)?.classList.add("hidden");
    });

    const activeForm = showRegister
      ? document.getElementById("registerForm")
      : document.getElementById("loginForm");
    const activeInput = activeForm.querySelector('input:not([type="hidden"])');
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    incomingSection.classList.remove("hidden");
    incomingSection.removeAttribute("aria-hidden");
    incomingSection.removeAttribute("inert");

    if (reduceMotion) {
      outgoingSection.classList.add("hidden");
      activeInput.focus();
      return;
    }

    const direction = showRegister ? "forward" : "backward";
    const enterClass = `auth-panel-enter-${direction}`;
    const exitClass = `auth-panel-exit-${direction}`;
    const startingHeight = outgoingSection.offsetHeight;
    authPanelSwitcher.style.height = `${startingHeight}px`;
    authPanelSwitcher.classList.add("is-transitioning");
    outgoingSection.setAttribute("aria-hidden", "true");
    outgoingSection.setAttribute("inert", "");
    incomingSection.classList.add(enterClass);
    outgoingSection.classList.add(exitClass);
    activeInput.focus();

    const targetHeight = incomingSection.offsetHeight;
    void authPanelSwitcher.offsetHeight;

    window.requestAnimationFrame(() => {
      authPanelSwitcher.style.height = `${targetHeight}px`;
    });

    let transitionFinished = false;
    let fallbackTimer;
    const finishTransition = () => {
      if (transitionFinished) return;
      transitionFinished = true;
      window.clearTimeout(fallbackTimer);
      incomingSection.removeEventListener("animationend", onAnimationEnd);
      outgoingSection.classList.add("hidden");
      outgoingSection.classList.remove(exitClass);
      outgoingSection.removeAttribute("aria-hidden");
      outgoingSection.removeAttribute("inert");
      incomingSection.classList.remove(enterClass);
      authPanelSwitcher.classList.remove("is-transitioning");
      authPanelSwitcher.style.height = "";
    };
    const onAnimationEnd = (event) => {
      if (event.target === incomingSection && event.animationName === enterClass) {
        finishTransition();
      }
    };

    incomingSection.addEventListener("animationend", onAnimationEnd);
    fallbackTimer = window.setTimeout(finishTransition, 600);
  });
});
