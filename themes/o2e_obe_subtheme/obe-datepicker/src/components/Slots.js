import React, { useEffect } from "react";
import moment from "moment";
import Accordion from "./Accordion";
import nextBtnHandler from "./nextBtnHandler";
export default function Slots(props) {
  // Array for available dates.
  let availableDates = [];

  //This will updated from calendar.
  let currentMoment = moment(props.selectedDate);

  // Setting up 3 days from today.
  let optionsDay = 0;
  let day1 = {
      date: parseInt(currentMoment.clone().format("DD")),
      day: currentMoment.clone().format("ddd"),
    },
    day2 = {
      date: parseInt(currentMoment.clone().add(1, "days").format("DD")),
      day: currentMoment.clone().add(1, "days").format("ddd"),
    },
    day3 = {
      date: parseInt(currentMoment.clone().add(2, "days").format("DD")),
      day: currentMoment.clone().add(2, "days").format("ddd"),
    };

  availableDates.push(day1, day2, day3);

  // Initialize main array and 3 subarrays for each day that will be in the radio table.
  let optionsByDay = [
    { morning: [], afternoon: [], evening: [] },
    { morning: [], afternoon: [], evening: [] },
    { morning: [], afternoon: [], evening: [] },
    { morning: [], afternoon: [], evening: [] },
  ];

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

  // Loop through each timeslot and group them by date.
  for (let key in props.timeslots) {
    let { start, finish } = props.timeslots[key];

    // Setting up moment object.
    let iMoment = moment(start).utc();
    let iDate = iMoment.clone().format("DD");
    let endMoment = moment(finish).utc();

    // set the options day to 1, 2, or 3, depending on the Y-m-d of this timeslot
    if (parseInt(day1.date) === parseInt(iDate)) optionsDay = 1;
    else if (parseInt(day2.date) === parseInt(iDate)) optionsDay = 2;
    else if (parseInt(day3.date) === parseInt(iDate)) optionsDay = 3;
    else optionsDay = 0;

    let slotHours = iMoment.clone().format("HH");
    let slotMinutes = iMoment.clone().format("mm");
    let radioBtnTemplate = (
      <div>
        <input
          type="radio"
          id={iMoment.clone().format()}
          onChange={updateWebform}
          name="timeSlot"
          data-start={iMoment.clone().format()}
          data-finish={endMoment.clone().format()}
          value={iMoment.clone().format()}
        ></input>
        <label for={iMoment.clone().format()}>
          {iMoment.format("hh:mm A")} -{" "}
          {iMoment.clone().add(2, "hours").format("hh:mm A")}
        </label>
      </div>
    );

    // Push the input radios into array based on their date and time.
    if (slotHours < 12 && slotMinutes < 31) {
      if (optionsByDay[optionsDay].hasOwnProperty("morning")) {
        optionsByDay[optionsDay].morning.push(radioBtnTemplate);
      }
    } else if (slotHours < 16 && slotMinutes < 31) {
      if (optionsByDay[optionsDay].hasOwnProperty("afternoon")) {
        optionsByDay[optionsDay].afternoon.push(radioBtnTemplate);
      }
    } else {
      if (optionsByDay[optionsDay].hasOwnProperty("evening")) {
        optionsByDay[optionsDay].evening.push(radioBtnTemplate);
      }
    }
  }

  // Initializing Render array for all accordions.
  let accordionGroup = [];

  //Pushing accordions in the array.
  optionsByDay.forEach((option, index) => {
    if (index > 0) {
      accordionGroup.push(
        <Accordion
          items={option}
          dayInfo={availableDates[index - 1]}
          index={index}
        />
      );
    }
  });

  // Cleaning up on re-renders
  useEffect(() => {
    return function cleanUp() {
      startTimeField.value = "";
      finshTimeField.value = "";
      pickUpField.value = "";
      arrivalTimeField.value = "";
    };
  });

  return <div className="row fadein">{accordionGroup}</div>;
}
