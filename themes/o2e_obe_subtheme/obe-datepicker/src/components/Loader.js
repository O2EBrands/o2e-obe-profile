var loaderString =
  drupalSettings.brand_name === "GJ AU" || drupalSettings.brand_name === "GJ NA"
    ? Drupal.t(
        "In just 15 seconds, we'll have some pick up times for you"
      )
    : Drupal.t("Checking available time slots");
//calendar loader fnx on page 2
function Loader() {
  return (
    <div className="obe-loader">
      <span className="loader-title"> {loaderString}... </span>
      <div class="spinner">
        <div class="bounce1"></div>
        <div class="bounce2"></div>
        <div class="bounce3"></div>
      </div>
    </div>
  );
}

export default Loader;
