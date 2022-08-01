import React, { useState, useMemo, useEffect } from "react";
import moment from "moment";
import DatePicker from "react-datepicker";
import Loader from "./Loader";
import Slots from "./Slots";
// import 'react-datepicker/dist/react-datepicker-cssmodules.css';
import "react-datepicker/dist/react-datepicker.css";

//Initialize object for response
let data = {};

function App() {
  //Initializing the startDate once per lifecycle.
  const startDate = useMemo(() => moment(), []);
  const [selectedDate, setSelectedDate] = useState(startDate);
  const [isLoading, setLoader] = useState(true);

  let apiStartDate = selectedDate.clone().format("YYYY-MM-D");
  let apiEndDate = selectedDate.clone().add(2, "days").format("YYYY-MM-D");

  useEffect(() => {
    let apiWithParam = `http://o2e.lndo.site:8000/availabletime?start_date=${apiStartDate}&end_date=${apiEndDate}`;
    console.log("Date changed", apiWithParam);
    //API calling and parsing logic
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
      <div className="col-lg-3 col-md-6 col-sm-12">
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
          <Loader />
        ) : (
          <Slots {...data} selectedDate={selectedDate} />
        )}
      </div>
    </div>
  );
}

export default App;
