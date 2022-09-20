import React from "react";
import ReactDOM from "react-dom/client";
import App from "./components/App";
import reportWebVitals from "./reportWebVitals";

(function ($, Drupal, once, drupalSettings) {
  Drupal.behaviors.reactDatepicker = {
    attach: function (context, settings) {
      let reactMountPoint = document.querySelector(
        'div[data-drupal-selector="edit-react-datepicker-component"]'
      );

      once("reactDatepicker", "html", context).forEach(function (element) {
        if (reactMountPoint) {
          const root = ReactDOM.createRoot(reactMountPoint);
          root.render(
            <React.StrictMode>
              <App />
            </React.StrictMode>
          );
          // If you want to start measuring performance in your app, pass a function
          // to log results (for example: reportWebVitals(console.log))
          // or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
          reportWebVitals();
        }
      });
    },
  };
})(jQuery, Drupal, once, drupalSettings);
