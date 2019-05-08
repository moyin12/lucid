window.onload = function() {
  // disable default behavior for forms (reload once a buttun is clicked)
  const form = document.querySelector("#installForm");
  // handle form switching
  let steps = document.getElementsByClassName("step-form");
  let stepOneForm = steps[0];
  let stepTwoForm = steps[1];
  document.getElementById("nextButton").addEventListener("click", function() {
    event.preventDefault();
    // stepOneForm.classList.add("animated", "fadeOutLeft");
    stepOneForm.style.display = "none";
    stepTwoForm.style.display = "initial";
    // stepTwoForm.classList.add("animated", "fadeInRight");
  });
  document
    .getElementById("previousButton")
    .addEventListener("click", function() {
      event.preventDefault();
      stepTwoForm.style.display = "none";
      stepOneForm.style.display = "initial";
    });

  // make sure accept terms and conditions is checked before enabling install button
  let acceptTermsButton = document.querySelector("#acceptTerms");
  let installButton = document.getElementById("installButton");

  acceptTermsButton.addEventListener("change", function() {
    installButton.toggleAttribute("disabled");
  });

  installButton.addEventListener("click", function() {
    if (!acceptTermsButton.checked && !installButton.hasAttribute("disabled")) {
      event.preventDefault();
      document.getElementById("validateTerms").textContent =
        "Kindly accept terms& conditions";
    } else {
      document.getElementById("validateTerms").textContent = "";
    }
  });
};
