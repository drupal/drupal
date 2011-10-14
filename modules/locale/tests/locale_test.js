
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


Drupal.formatPlural(1, "Standard Call plural", "Standard Call @count plural");
Drupal
.
formatPlural
(
1,
"Whitespace Call plural",
"Whitespace Call @count plural",
)
;

Drupal.formatPlural(1, 'Single Quote plural', 'Single Quote @count plural');
Drupal.formatPlural(1, 'Single Quote \'Escaped\' plural', 'Single Quote \'Escaped\' @count plural');

Drupal.formatPlural(1, "Double Quote plural", "Double Quote @count plural");
Drupal.formatPlural(1, "Double Quote \"Escaped\" plural", "Double Quote \"Escaped\" @count plural");
