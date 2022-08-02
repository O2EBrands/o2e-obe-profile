import React, { useState } from "react";

export default function AccordionItem({
  timeOfTheDay,
  accordionId,
  itemsArray,
}) {
  // State for more button.
  const [isExpanded, setExpanded] = useState(false);

  return (
    <div className="accordion-item">
      <h3
        className="accordion-header"
        id={`headingOne${accordionId}${timeOfTheDay}`}
      >
        {itemsArray[timeOfTheDay].length ? (
          <button
            onClick={() => setExpanded(false)}
            className={`accordion-button ${timeOfTheDay ? "collapsed" : ""}`}
            type="button"
            data-bs-toggle="collapse"
            data-bs-target={`#collapseOne${accordionId}${timeOfTheDay}`}
            aria-expanded={timeOfTheDay ? "false" : "true"}
            aria-controls={`collapseOne${accordionId}${timeOfTheDay}`}
          >
            <p>
              <strong>{timeOfTheDay}</strong>
              <span>
                {itemsArray[timeOfTheDay].length} timeslots available.
              </span>
            </p>
          </button>
        ) : (
          <p className="accordion-button collapsed">
            <strong>{timeOfTheDay}</strong>
            <span>{itemsArray[timeOfTheDay].length} timeslots available.</span>
          </p>
        )}
      </h3>
      {itemsArray[timeOfTheDay].length ? (
        <div
          id={`collapseOne${accordionId}${timeOfTheDay}`}
          className={`accordion-collapse collapse ${
            timeOfTheDay ? "" : "show"
          }`}
          aria-labelledby={`headingOne${accordionId}${timeOfTheDay}`}
          data-bs-parent={`#accordion${accordionId}`}
        >
          <div className="accordion-body">
            {isExpanded || itemsArray[timeOfTheDay].length < 5
              ? itemsArray[timeOfTheDay]
              : itemsArray[timeOfTheDay].slice(0, 4)}

            {itemsArray[timeOfTheDay].length > 4 ? (
              <span
                className="btn-expand"
                onClick={() => setExpanded(!isExpanded)}
              >
                {isExpanded ? "Less" : "More"}
              </span>
            ) : (
              ""
            )}
          </div>
        </div>
      ) : (
        ""
      )}
    </div>
  );
}
