import React, { useState, useMemo, useEffect } from "react";
import moment from "moment";
import DatePicker from "react-datepicker";
import Loader from "./Loader";
import Slots from "./Slots";
import SlotLoader from "./SlotLoader";
import nextBtnHandler from "./nextBtnHandler";
// import 'react-datepicker/dist/react-datepicker-cssmodules.css';
import "react-datepicker/dist/react-datepicker.css";

//Initialize object for response
let data = {};

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
      currentDate = moment().utc();
    }
  } else {
    currentDate = moment().utc();
  }

  //Initializing the startDate once per lifecycle.
  const startDate = useMemo(() => moment(), []);
  const [selectedDate, setSelectedDate] = useState(currentDate);
  const [isLoading, setLoader] = useState(true);

  // Disabling Next button if values not set.
  useEffect(() => {
    // Initializing button handler.
    nextBtnHandler();
  });

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

  return (
    <div className="row fadein">
      <div className="col-lg-3 col-md-6 col-sm-12 datepicker-wrapper">
        {isLoading ? <Loader /> : ""}
        <DatePicker
          selected={new Date(selectedDate.clone().format("YYYY-MM-D"))}
          onChange={(date: Date) => setSelectedDate(moment(date).utc())}
          minDate={new Date(startDate.clone().format("YYYY-MM-D"))}
          maxDate={new Date(startDate.clone().add(4, "months").format("YYYY-MM-D"))}
          inline
        />
      </div>
      <div className="col-lg-9 col-md-6 col-sm-12">
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
