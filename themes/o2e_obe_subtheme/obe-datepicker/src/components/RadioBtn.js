import React from "react";
import moment from "moment";
import nextBtnHandler from "./nextBtnHandler";

export default function RadioBtn(props) {
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
  let radioId = props.startMoment.clone().format("MMMDHm");

  // Function to keep the DOM values in sync.
  function updateWebform(event) {
    // fetching selected values.
    let startValue = event.target.getAttribute("data-start");
    let finishValue = event.target.getAttribute("data-finish");
    let pickUpValue = moment(startValue).utc().format("ddd, MMM D, YYYY");
    let arrivalTimeValue = `${moment(startValue)
      .utc()
      .format("hh:mm A")} - ${moment(startValue)
      .utc()
      .add(2, "hours")
      .format("hh:mm A")}`;

    // Updating the values.
    startTimeField.value = startValue.toString();
    finshTimeField.value = finishValue.toString();
    pickUpField.value = pickUpValue.toString();
    arrivalTimeField.value = arrivalTimeValue.toString();

    //Updating next button state.
    nextBtnHandler();
  }
  return (
    <div
      className={`slot-item ${
        startTimeField.value === props.startMoment.clone().format().toString()
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
      <label for={radioId}>
        {props.startMoment.format("hh:mm")} -{" "}
        {props.startMoment.clone().add(2, "hours").format("hh:mm A")}
      </label>
    </div>
  );
}
