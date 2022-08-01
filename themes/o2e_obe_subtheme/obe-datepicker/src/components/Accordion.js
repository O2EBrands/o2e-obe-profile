import React from "react";
import AccordionItem from "./AccordionItem";

const timesOfTheDay = ["morning", "afternoon", "evening"];

export default function Accordion(props) {
  // Initializing All slots for a single day.
  let itemsArray = props.items;

  // index for accordion if there are multiple accordions.
  let accordionId = props.index;
  let timeOfTheDay = 0;

  // Accordion item generator.
  function accordionItemGenerate(timeOfTheDay, accordionId) {
    return (
      <React.Fragment key={accordionId}>
        <AccordionItem
          timeOfTheDay={timeOfTheDay}
          accordionId={accordionId}
          itemsArray={itemsArray}
        />
      </React.Fragment>
    );
  }

  return (
    <div className="col-lg-4 accordion" id={`accordion${accordionId}`}>
      <h3 className="slot-day-title">
        {props.dayInfo.date},&nbsp;{props.dayInfo.day}
      </h3>
      {timesOfTheDay.map((timeOfTheDay) => {
        return (
          <React.Fragment key={timeOfTheDay}>
            {accordionItemGenerate(timeOfTheDay, accordionId)}
          </React.Fragment>
        );
      })}
    </div>
  );
}
