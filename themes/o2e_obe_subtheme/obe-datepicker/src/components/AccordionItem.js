import React, { useState, useEffect } from "react";

var availableString = Drupal.t("available");
var lessString = Drupal.t("Less");
var moreString = Drupal.t("More");
var morningString = Drupal.t("morning");
var eveningString = Drupal.t("evening");
var afternoonString = Drupal.t("afternoon");

// function for collapsing other accordion-items.
function collapseOthers(event) {
  // Target collapse element.
  let currentCollapse = jQuery(event.currentTarget)
    .parent(".accordion-item")
    .find(".accordion-collapse");

  // Close all other accordion-items except current.
  jQuery(".accordion-collapse").not(currentCollapse).collapse("hide");
}

export default function AccordionItem({
  timeOfTheDay,
  accordionId,
  itemsArray,
}) {
  // State for more button.
  const [isExpanded, setExpanded] = useState(false);

  //DOM mutations for preset date and opening accordion .
  useEffect(() => {
    setExpanded(true);
    setTimeout(() => {
      if (jQuery(".pre-selected").length) {
        jQuery(".pre-selected").find("input").prop("checked", "true");
        let activeAccordion = jQuery(".pre-selected").parents(
          ".accordion-item"
        );
        activeAccordion.find(".accordion-button").removeClass("collapsed");
        activeAccordion.find(".accordion-collapse").addClass("show");
        jQuery(".accordion-button.collapsed")
          .parents(".accordion-item")
          .find(".btn-expand")
          .click();
      } else {
        setExpanded(false);
      }
    }, 100);
  }, []);

  return (
    <div className="accordion-item">
      <h3
        className="accordion-header"
        id={`headingOne${accordionId}${timeOfTheDay}`}
      >
        {itemsArray[timeOfTheDay].length ? (
          <button
            onClick={(event) => {
              setExpanded(false);
              collapseOthers(event);
            }}
            className={`accordion-button ${timeOfTheDay ? "collapsed" : ""}`}
            type="button"
            data-bs-toggle="collapse"
            data-bs-target={`#collapseOne${accordionId}${timeOfTheDay}`}
            aria-expanded={timeOfTheDay ? "false" : "true"}
            aria-controls={`collapseOne${accordionId}${timeOfTheDay}`}
          >
            <p>
              <strong>
                {timeOfTheDay === "morning"
                  ? morningString
                  : timeOfTheDay === "evening"
                  ? eveningString
                  : timeOfTheDay === "afternoon"
                  ? afternoonString
                  : ""}
              </strong>
              <small>
                {itemsArray[timeOfTheDay].length} {availableString}
              </small>
            </p>
          </button>
        ) : (
          <p className="accordion-button collapsed">
            <strong>
              {timeOfTheDay === "morning"
                ? morningString
                : timeOfTheDay === "evening"
                ? eveningString
                : timeOfTheDay === "afternoon"
                ? afternoonString
                : ""}
            </strong>
            <small>
              {itemsArray[timeOfTheDay].length} {availableString}
            </small>
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
                {isExpanded ? lessString : moreString}
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
