/**
 * @file
 * JavaScript for locale_test.module.
 *
 * @ignore
 */

Drupal.t("Standard Call t");
Drupal
  .
  t
  (
    "Whitespace Call t"
  )
;

Drupal.t('Single Quote t');
Drupal.t('Single Quote \'Escaped\' t');
Drupal.t('Single Quote ' + 'Concat ' + 'strings ' + 't');

Drupal.t("Double Quote t");
Drupal.t("Double Quote \"Escaped\" t");
Drupal.t("Double Quote " + "Concat " + "strings " + "t");

Drupal.t("Context Unquoted t", {}, {context: "Context string unquoted"});
Drupal.t("Context Single Quoted t", {}, {'context': "Context string single quoted"});
Drupal.t("Context Double Quoted t", {}, {"context": "Context string double quoted"});

Drupal.t("Context !key Args t", {'!key': 'value'}, {context: "Context string"});

Drupal.formatPlural(1, "Standard Call @count singular", "Standard Call @count plural");
Drupal
  .
  formatPlural
  (
    1,
    "Whitespace Call @count singular",
    "Whitespace Call @count plural"
  )
;

Drupal.formatPlural(1, 'Single Quote @count singular', 'Single Quote @count plural');
Drupal.formatPlural(1, 'Single Quote \'Escaped\' @count singular', 'Single Quote \'Escaped\' @count plural');

Drupal.formatPlural(1, "Double Quote @count singular", "Double Quote @count plural");
Drupal.formatPlural(1, "Double Quote \"Escaped\" @count singular", "Double Quote \"Escaped\" @count plural");

Drupal.formatPlural(1, "Context Unquoted @count singular", "Context Unquoted @count plural", {}, {context: "Context string unquoted"});
Drupal.formatPlural(1, "Context Single Quoted @count singular", "Context Single Quoted @count plural", {}, {'context': "Context string single quoted"});
Drupal.formatPlural(1, "Context Double Quoted @count singular", "Context Double Quoted @count plural", {}, {"context": "Context string double quoted"});

Drupal.formatPlural(1, "Context !key Args @count singular", "Context !key Args @count plural", {'!key': 'value'}, {context: "Context string"});

Drupal.formatPlural(1, "No count argument plural - singular", "No count argument plural - plural");
