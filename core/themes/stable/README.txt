
ABOUT STABLE
------------

Stable is a base theme with minimal markup and very few classes. It functions
as a backwards compatibility layer for Drupal 8's core markup and CSS. Stable
allows markup and styling to evolve throughout the life of Drupal 8, while still
providing themes a stable base for the clean, minimal markup provided by core.

If you browse Stable's contents, you will find copies of all the Twig templates
and CSS files provided by core. This ensures that changes made to core markup
and styling do not affect themes using Stable as a base theme.

Stable will be used as the base theme if no base theme is set in a theme's
.info.yml file. To opt out of Stable you can set the base theme to false in
your theme's .info.yml file (see the warning below before doing this):
base theme: false

Warning: Themes that opt out of using Stable will need continuous maintenance as
core changes, so only opt out if you are prepared to keep track of those changes
and how they affect your theme.

ABOUT DRUPAL THEMING
--------------------

For more information, see Drupal.org's theming guide.
https://www.drupal.org/theme-guide/8
