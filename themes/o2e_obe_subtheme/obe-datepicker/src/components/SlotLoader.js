import React from "react";

// Declaring render arrays.
let accordionItems = [];
let renderArray = [];

// Populating accordionItems.
for (let i = 0; i < 3; i++) {
  accordionItems.push(
    <div className="accordion-item">
      <div className="accordion-header" id="headingOne1morning">
        <p
          className="accordion-button collapsed"
          style={{ height: "80px" }}
        ></p>
      </div>
    </div>
  );
}

//Populating final render array.
for (let i = 0; i < 3; i++) {
  renderArray.push(
    <div className="col-lg-4 accordion">
      <p className="slot-day-title" style={{ height: "54px" }}></p>
      {accordionItems}
    </div>
  );
}

export default function SlotLoader() {
  return <div className="row pulsing">{renderArray}</div>;
}
