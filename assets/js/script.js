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

document.querySelectorAll("[data-numeric-only]").forEach((input) => {
  input.addEventListener("input", () => {
    input.value = input.value.replace(/\D/g, "");
  });
});

const roleSelect = document.querySelector("#role");
const loginIdLabel = document.querySelector("#login-id-label");
const loginIdInput = document.querySelector("#login_id");

if (roleSelect && loginIdLabel && loginIdInput) {
  const updateLoginId = () => {
    const isRegistrar = roleSelect.value === "registrar";
    loginIdLabel.textContent = isRegistrar ? "Registrar ID" : "School ID";
    loginIdInput.placeholder = isRegistrar
      ? "Enter your registrar ID"
      : "Enter your school ID";
  };

  roleSelect.addEventListener("change", updateLoginId);
  updateLoginId();
}

const documentTypeSelect = document.querySelector('[name="document_type"]');
const additionalDetailsGroup = document.querySelector("#additional-details-group");
const additionalDetailsInput = document.querySelector("#additional_details");

if (documentTypeSelect && additionalDetailsGroup && additionalDetailsInput) {
  const updateAdditionalDetails = () => {
    const isOther = documentTypeSelect.value === "Other";
    additionalDetailsGroup.hidden = !isOther;
    additionalDetailsInput.disabled = !isOther;
    additionalDetailsInput.required = isOther;
  };

  documentTypeSelect.addEventListener("change", updateAdditionalDetails);
  updateAdditionalDetails();
}
