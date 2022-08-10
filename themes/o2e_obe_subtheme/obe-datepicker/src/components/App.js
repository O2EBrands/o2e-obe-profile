import React, { useState, useMemo, useEffect } from "react";
import moment from "moment";
import DatePicker from "react-datepicker";
import Loader from "./Loader";
import Slots from "./Slots";
import SlotLoader from "./SlotLoader";
// import 'react-datepicker/dist/react-datepicker-cssmodules.css';
import "react-datepicker/dist/react-datepicker.css";

//Initialize object for response
let data = {};

function App() {
  //Initializing the startDate once per lifecycle.
  const startDate = useMemo(() => moment(), []);
  const [selectedDate, setSelectedDate] = useState(startDate);
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

  return (
    <div className="row fadein">
      <div className="col-lg-3 col-md-6 col-sm-12 datepicker-wrapper">
        {isLoading ? <Loader /> : ""}
        <DatePicker
          selected={selectedDate.clone().toDate()}
          onChange={(date: Date) => setSelectedDate(moment(date))}
          minDate={startDate.clone().toDate()}
          maxDate={startDate.clone().add(3, "months").toDate()}
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
