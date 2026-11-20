document.querySelectorAll("form").forEach((form) => {
  form.addEventListener("submit", (event) => {
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
    }
  });
});

document.querySelectorAll("[data-confirm]").forEach((element) => {
  element.addEventListener("click", (event) => {
    if (!window.confirm(element.dataset.confirm)) {
      event.preventDefault();
    }
  });
});
