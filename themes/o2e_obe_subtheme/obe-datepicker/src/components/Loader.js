var loaderString = Drupal.t("Checking available time slots");
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
