export default function nextBtnHandler() {
  // Getting dom objects for selecting values.
  let startTimeField = document.querySelector(
    'input[data-drupal-selector="edit-start-date-time"]'
  );
  let finshTimeField = document.querySelector(
    'input[data-drupal-selector="edit-finish-date-time"]'
  );
  let pickUpField = document.querySelector(
    'input[data-drupal-selector="edit-pick-up-date"]'
  );
  let arrivalTimeField = document.querySelector(
    'input[data-drupal-selector="edit-arrival-time"]'
  );

  // driver Logic.
  if (
    startTimeField.value === "" ||
    finshTimeField.value === "" ||
    pickUpField.value === "" ||
    arrivalTimeField.value === ""
  ) {
    jQuery(".webform-button--next").addClass("disabled");
  } else {
    jQuery(".webform-button--next").removeClass("disabled");
  }
}
