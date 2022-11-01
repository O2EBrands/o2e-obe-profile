import React, { useEffect } from "react";
import moment from "moment";
import Accordion from "./Accordion";
import RadioBtn from "./RadioBtn";

// Initializing months.
var month1 = Drupal.t("Jan");
var month2 = Drupal.t("Feb");
var month3 = Drupal.t("Mar");
var month4 = Drupal.t("Apr");
var month5 = Drupal.t("May");
var month6 = Drupal.t("Jun");
var month7 = Drupal.t("July");
var month8 = Drupal.t("Aug");
var month9 = Drupal.t("Sep");
var month10 = Drupal.t("Oct");
var month11 = Drupal.t("Nov");
var month12 = Drupal.t("Dec");

// Initializing days.
var day1 = Drupal.t("Sun");
var day2 = Drupal.t("Mon");
var day3 = Drupal.t("Tue");
var day4 = Drupal.t("Wed");
var day5 = Drupal.t("Thu");
var day6 = Drupal.t("Fri");
var day7 = Drupal.t("Sat");

// Creating array for month.
let monthsArray = [
  month1,
  month2,
  month3,
  month4,
  month5,
  month6,
  month7,
  month8,
  month9,
  month10,
  month11,
  month12,
];

// Creating array for days.
let daysArray = [day1, day2, day3, day4, day5, day6, day7];

export default function Slots(props) {
  // Array for available dates.
  let availableDates = [];

  // This will updated from calendar.
  let currentMoment = moment(props.selectedDate);
  let isToday = currentMoment.isSame(moment(), "day");

  // Set the timeslot selectedDate to maxDate if currentDate is ahead of maxdate.
  currentMoment = currentMoment.isAfter(props.maxDate)
    ? props.maxDate
    : currentMoment;

  // Setting up 3 days from today.
  let optionsDay = 0;
  let day1 = {
      date: parseInt(currentMoment.clone().format("DD")),
      day: daysArray[parseInt(currentMoment.clone().format("d"))],
      month: monthsArray[parseInt(currentMoment.clone().format("M")) - 1],
    },
    day2 = {
      date: parseInt(currentMoment.clone().add(1, "days").format("DD")),
      day:
        daysArray[parseInt(currentMoment.clone().add(1, "days").format("d"))],
      month:
        monthsArray[
          parseInt(currentMoment.clone().add(1, "days").format("M")) - 1
        ],
    },
    day3 = {
      date: parseInt(currentMoment.clone().add(2, "days").format("DD")),
      day:
        daysArray[parseInt(currentMoment.clone().add(2, "days").format("d"))],
      month:
        monthsArray[
          parseInt(currentMoment.clone().add(2, "days").format("M")) - 1
        ],
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
      <RadioBtn startMoment={iMoment} endMoment={endMoment} />
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
          today={index === 1 ? isToday : false}
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
