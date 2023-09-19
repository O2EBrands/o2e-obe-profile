import React from "react";
import AccordionItem from "./AccordionItem";

const timesOfTheDay = ["morning", "afternoon", "evening"];
var todayString = Drupal.t("today");

export default function Accordion(props) {
  // Initializing All slots for a single day.
  let itemsArray = props.items;

  // index for accordion if there are multiple accordions.
  let accordionId = props.index;

  // classes on accordion wrapper
  let accordionClass = props.classes;
  
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
    <div className={`accordion ${accordionClass}`} id={`accordion${accordionId}`}>
      <h3 className="slot-day-title">
        {props.today &&
        (drupalSettings.brand_name === "GJ NA" ||
          drupalSettings.brand_name === "GJ AU")
          ? todayString
          : `${props.dayInfo.day}, ${props.dayInfo.month} ${props.dayInfo.date}`}
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
