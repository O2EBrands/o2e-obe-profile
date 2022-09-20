// currentLanguage handler function.

export default function currentLangHandler() {
  //Initialize current language var
  let currentLanguage = "en";

  // Logic to determine if language is Canada_Francis.
  if (drupalSettings.path.currentLanguage === "fr-ca") {
    currentLanguage = "fr";
  }
  return currentLanguage;
}
