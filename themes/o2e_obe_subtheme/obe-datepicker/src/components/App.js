import React, { useState, useMemo, useEffect } from "react";
import moment from "moment-timezone";
import DatePicker from "react-datepicker";
import { registerLocale } from "react-datepicker";
import Loader from "./Loader";
import Slots from "./Slots";
import SlotLoader from "./SlotLoader";
import { fr } from "date-fns/locale";

// import 'react-datepicker/dist/react-datepicker-cssmodules.css';
import "react-datepicker/dist/react-datepicker.css";

// import currentLanguage handler.
import currentLangHandler from "./currentLangHandler";
let currentLanguage = currentLangHandler();

// Translation language registration for datepicker to Canada_Francis.
registerLocale("fr", fr);

//Initialize object for response
let data = {};

// Current date string.
let today = new Date();
let curDateString =
  today.getFullYear() +
  "-" +
  ("0" + (today.getMonth() + 1)).slice(-2) +
  "-" +
  ("0" + today.getDate()).slice(-2);

function App() {
  //Fetching hidden field.
  let startTimeField = document.querySelector(
    'input[data-drupal-selector="edit-start-date-time"]'
  );

  //Check if date is already set.
  let currentDate;
  if (startTimeField) {
    // If already date is selected then convert it into UTC.
    if (startTimeField.value !== "" && localStorage.getItem("timeZone")) {
      let tempDate = moment
        .tz(startTimeField.value, localStorage.getItem("timeZone"))
        .tz("utc", true)
        .format();
      currentDate = moment(tempDate).utc();
    } else {
      startTimeField.value = "";
      currentDate = moment.utc(curDateString);
    }
  } else {
    currentDate = moment.utc(curDateString);
  }

  //Initializing the startDate once per lifecycle.
  const startDate = useMemo(() => moment(), []);
  const [selectedDate, setSelectedDate] = useState(currentDate);
  const [isLoading, setLoader] = useState(true);

  useEffect(() => {
    // Setting up dates for API call.
    let apiStartDate = selectedDate.clone().format("YYYY-MM-DD");
    let apiEndDate = selectedDate.clone().add(2, "days").format("YYYY-MM-DD");

    // API with parameters.
    let apiWithParam = Drupal.url(
      `availabletime?start_date=${apiStartDate}&end_date=${apiEndDate}`
    );

    //API calling and parsing logic.
    setLoader(true);
    fetch(apiWithParam)
      .then((res) => res.json())
      .then(
        (result) => {
          data = result;
          if (data.time_zone) {
            localStorage.setItem("timeZone", data.time_zone);
          }
          setLoader(false);
        },
        (error) => {
          setLoader(true);
        }
      );
  }, [selectedDate]);

  // Min date and Max date for Calendar.
  let minDate = startDate.clone();
  let maxDate =
    drupalSettings.brand_name === "GJ NA" ||
    drupalSettings.brand_name === "GJ AU"
      ? startDate.clone().add(4, "months").subtract(2, "days")
      : drupalSettings.brand_name === "W1D"
      ? startDate.clone().add(1, "years")
      : drupalSettings.brand_name === "SSH"
      ? startDate.clone().endOf("year").startOf("day").add(3, "years")
      : undefined;

  // input for datepicker maxDate.
  let datePickerMaxDateInput;
  // Set the datepicker selectedDate to maxDate if currentDate is ahead of maxdate.
  if (typeof maxDate == "undefined") {
    datePickerMaxDateInput = undefined;
  } else {
    if (maxDate && selectedDate.isAfter(maxDate)) {
      setSelectedDate(maxDate.clone());
    }
    datePickerMaxDateInput = new Date(
      maxDate.year(),
      maxDate.month(),
      maxDate.date()
    );
  }

  // Datepicker header formater according to brand.
  let reactDateFormat =
    drupalSettings.brand_name === "SSH" ? "MMMM yyyy" : "MMMM";

  return (
    <div className="row fadein">
      {isLoading ? <Loader /> : ""}
      <div className="col-lg-5 col-sm-7 col-xs-12 datepicker-wrapper">
        <DatePicker
          locale={currentLanguage}
          dateFormatCalendar={reactDateFormat}
          minDate={new Date(minDate.year(), minDate.month(), minDate.date())}
          maxDate={datePickerMaxDateInput}
          inline
          dayClassName={(date: Date) => {
            // day Date string
            let dateString =
              date.getFullYear() +
              "-" +
              ("0" + (date.getMonth() + 1)).slice(-2) +
              "-" +
              ("0" + date.getDate()).slice(-2);

            // days difference.
            let daysDiff = selectedDate.diff(dateString, "days", true);

            // add class to day.
            if (daysDiff >= -2 && daysDiff < 0) {
              return "datepicker-selected-group";
            }
          }}
          calendarStartDay={0}
          selected={
            new Date(
              selectedDate.year(),
              selectedDate.month(),
              selectedDate.date()
            )
          }
          onChange={(date: Date) => {
            let calDateString =
              date.getFullYear() +
              "-" +
              ("0" + (date.getMonth() + 1)).slice(-2) +
              "-" +
              ("0" + date.getDate()).slice(-2);
            setSelectedDate(moment.utc(calDateString));
          }}
        />
      </div>
      <div className="col-lg-7 col-sm-5 col-xs-12 timeslot-wrapper">
        {isLoading ? (
          <SlotLoader />
        ) : (
          <Slots {...data} selectedDate={selectedDate} maxDate={maxDate} />
        )}
      </div>
    </div>
  );
}

export default App;
