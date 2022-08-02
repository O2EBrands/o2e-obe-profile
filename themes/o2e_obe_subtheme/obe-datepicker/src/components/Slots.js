import React from "react";
import moment from "moment";
import Accordion from "./Accordion";
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

  //Function to keep the DOM values in sync on click.
  function updateWebform(event) {
    let startValue = event.target.getAttribute("data-start");
    let finishValue = event.target.getAttribute("data-finish");
    document.querySelectorAll(
      '[data-drupal-selector="edit-end-time"]'
    ).value = finishValue;
    document.querySelectorAll(
      '[data-drupal-selector="edit-start-time"]'
    ).value = startValue;
  }

  // Loop through each timeslot and group them by date.
  for (let key in props.timeslots) {
    let { start, finish } = props.timeslots[key];

    // Setting up moment object.
    let iMoment = moment(start).utc();
    let iDate = iMoment.clone().format("DD");
    let endMoment = moment(finish).utc();

    // set the options day to 1, 2, or 3, depending on the Y-m-d of this timeslot
    if (day1.date == iDate) optionsDay = 1;
    else if (day2.date == iDate) optionsDay = 2;
    else if (day3.date == iDate) optionsDay = 3;
    else optionsDay = 0;

    let slotHours = iMoment.clone().format("HH");
    let slotMinutes = iMoment.clone().format("mm");

    // Push the input radios into array based on their date and time.
    if (slotHours < 12 && slotMinutes < 31) {
      if (optionsByDay[optionsDay].hasOwnProperty("morning")) {
        optionsByDay[optionsDay].morning.push(
          <div>
            <input
              type="radio"
              onClick={updateWebform}
              name="timeSlot"
              data-start={iMoment.clone().format()}
              data-finish={endMoment.clone().format()}
              value={iMoment.clone().format()}
            ></input>
            <label>
              {iMoment.format("hh:mm A")} -
              {endMoment.add(2, "hours").format("hh:mm A")}
            </label>
          </div>
        );
      }
    } else if (slotHours < 16 && slotMinutes < 31) {
      if (optionsByDay[optionsDay].hasOwnProperty("afternoon")) {
        optionsByDay[optionsDay].afternoon.push(
          <div>
            <input
              type="radio"
              onClick={updateWebform}
              name="timeSlot"
              data-start={iMoment.clone().format()}
              data-finish={endMoment.clone().format()}
              value={iMoment.clone().format()}
            ></input>
            <label>
              {iMoment.format("hh:mm A")} -
              {endMoment.add(2, "hours").format("hh:mm A")}
            </label>
          </div>
        );
      }
    } else {
      if (optionsByDay[optionsDay].hasOwnProperty("evening")) {
        optionsByDay[optionsDay].evening.push(
          <div>
            <input
              onClick={updateWebform}
              id={iMoment.clone().format()}
              type="radio"
              name="timeSlot"
              data-start={iMoment.clone().format()}
              data-finish={endMoment.clone().format()}
              value={iMoment.clone().format()}
            ></input>
            <label>
              {iMoment.format("hh:mm A")} -
              {endMoment.add(2, "hours").format("hh:mm A")}
            </label>
          </div>
        );
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

  return <div className="row">{accordionGroup}</div>;
}
