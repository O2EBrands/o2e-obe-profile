import React, { useState, useMemo, useEffect } from "react";
import moment from "moment";
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
    if (startTimeField.value !== "") {
      currentDate = moment(startTimeField.value).utc();
    }
    if (startTimeField.value === "") {
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
    let apiStartDate = selectedDate.clone().format("YYYY-MM-D");
    let apiEndDate = selectedDate.clone().add(2, "days").format("YYYY-MM-D");

    // API with parameters.
    let hostname = window.location.origin;
    let apiWithParam = `${hostname}/availabletime?start_date=${apiStartDate}&end_date=${apiEndDate}`;

    //API calling and parsing logic.
    setLoader(true);
    fetch(apiWithParam)
      .then((res) => res.json())
      .then(
        (result) => {
          data = result;
          setLoader(false);
        },
        (error) => {
          setLoader(true);
        }
      );
  }, [selectedDate]);

  // Min date and Max date for Calendar.
  let minDate = startDate.clone();
  let maxDate = startDate.clone().subtract(2, "days").add(4, "months");

  return (
    <div className="row fadein">
      <div className="col-lg-5 col-sm-7 col-xs-12 datepicker-wrapper">
        {isLoading ? <Loader /> : ""}
        <DatePicker
          locale={currentLanguage}
          dateFormatCalendar="MMMM"
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
          minDate={new Date(minDate.year(), minDate.month(), minDate.date())}
          maxDate={new Date(maxDate.year(), maxDate.month(), maxDate.date())}
          inline
        />
      </div>
      <div className="col-lg-7 col-sm-5 col-xs-12 timeslot-wrapper">
        {isLoading ? (
          <SlotLoader />
        ) : (
          <Slots {...data} selectedDate={selectedDate} />
        )}
      </div>
    </div>
  );
}

export default App;
