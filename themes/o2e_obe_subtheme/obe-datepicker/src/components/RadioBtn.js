import React from "react";
import moment from "moment-timezone";

let startTimeField = { value: "" };
export default function RadioBtn(props) {
  let startGrp = Array.from(
    document.querySelectorAll(
      'input[data-drupal-selector="edit-start-date-time"], input[data-drupal-selector="edit-ssh-checkout-pane-calendar-availability-start-date-time"]'
    )
  );

  // Updating the values.
  startGrp.forEach((field) => {
    startTimeField.value = field.value;
  });

  let finishTimeField = Array.from(
    document.querySelectorAll(
      'input[data-drupal-selector="edit-finish-date-time"], input[data-drupal-selector="edit-ssh-checkout-pane-calendar-availability-finish-date-time"]'
    )
  );
  let pickUpField = Array.from(
    document.querySelectorAll(
      'input[data-drupal-selector="edit-pick-up-date"], input[data-drupal-selector="edit-ssh-checkout-pane-calendar-availability-pick-up-date"]'
    )
  );
  let arrivalTimeField = Array.from(
    document.querySelectorAll(
      'input[data-drupal-selector="edit-arrival-time"], input[data-drupal-selector="edit-ssh-checkout-pane-calendar-availability-arrival-time"]'
    )
  );
  let radioId = props.startMoment.clone().format("MMMDHm");

  // Function to keep the DOM values in sync.
  function updateWebform(event) {
    // fetching selected values.
    let startValue = event.target.getAttribute("data-start");
    let finishValue = event.target.getAttribute("data-finish");
    let pickUpValue = moment(startValue).utc().format("dddd, MMM D, YYYY");
    let arrivalTimeValue = `${moment(startValue)
      .utc()
      .format("h:mma")} - ${moment(startValue)
      .utc()
      .add(2, "hours")
      .format("h:mma")}`;

    // Updating the values.
    startGrp.forEach((field) => {
      field.value = moment.utc(startValue).tz(props.timeZone, true).format();
      startTimeField.value = field.value;
    });

    finishTimeField.forEach((field) => {
      field.value = moment.utc(finishValue).tz(props.timeZone, true).format();
    });

    pickUpField.forEach((field) => {
      field.value = pickUpValue.toString();
    });

    arrivalTimeField.forEach((field) => {
      field.value = arrivalTimeValue.toString();
    });
  }

  // default formatted timeslot
  let formattedTimeSlot = `${props.startMoment.format(
    "h:mm"
  )} - ${props.startMoment.clone().add(2, "hours").format("h:mm A")}`;

  // Updating timeslots based on Site.
  switch (drupalSettings.brand_name) {
    // Setting format for GJ NA.
    case "GJ NA":
    case "GJ AU":
      formattedTimeSlot = `${props.startMoment.format(
        "h:mm"
      )} - ${props.startMoment.clone().add(2, "hours").format("h:mm A")}`;
      break;
    default:
      formattedTimeSlot = props.startMoment.format("h:mm a");
      break;
  }

  return (
    <div
      className={`slot-item ${
        moment
          .tz(startTimeField.value, localStorage.getItem("timeZone"))
          .tz("utc", true)
          .format() === props.startMoment.clone().format().toString()
          ? "pre-selected"
          : ""
      }`}
    >
      <input
        type="radio"
        id={radioId}
        onChange={updateWebform}
        name="timeSlot"
        data-start={props.startMoment.clone().format()}
        data-finish={props.endMoment.clone().format()}
        value={props.startMoment.clone().format()}
      ></input>
      <label for={radioId}>{formattedTimeSlot}</label>
    </div>
  );
}
