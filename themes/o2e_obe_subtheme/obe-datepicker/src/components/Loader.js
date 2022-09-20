var loaderString = Drupal.t("Checking available time slots");
function Loader() {
  return (
    <div className="obe-loader">
      <span className="loader-title"> {loaderString}... </span>
      <div className="dot-pulse"></div>
    </div>
  );
}

export default Loader;
