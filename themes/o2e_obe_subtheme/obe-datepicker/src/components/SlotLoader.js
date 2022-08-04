import React from "react";

// Declaring render arrays.
let accordionItems = [];
let renderArray = [];

// Populating accordionItems.
for (let i = 0; i < 3; i++) {
  accordionItems.push(
    <div className="accordion-item">
      <h3 className="accordion-header" id="headingOne1morning">
        <p
          className="accordion-button collapsed"
          style={{ height: "80px" }}
        ></p>
      </h3>
    </div>
  );
}

//Populating final render array.
for (let i = 0; i < 3; i++) {
  renderArray.push(
    <div className="col-lg-4 accordion">
      <h3 className="slot-day-title" style={{ height: "54px" }}></h3>
      {accordionItems}
    </div>
  );
}

export default function SlotLoader() {
  return <div className="row pulsing">{renderArray}</div>;
}
