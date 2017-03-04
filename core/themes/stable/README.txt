
ABOUT STABLE
------------

Stable is the default base theme; it provides minimal markup and very few
CSS classes. If you prefer more structured markup see the Classy base theme.

Stable allows core markup and styling to evolve by functioning as a backwards
compatibility layer for themes against changes to core markup and CSS. If you
browse Stable's contents, you will find copies of all the Twig templates and
CSS files provided by core.

Stable will be used as the base theme if no base theme is set in a theme's
.info.yml file. To opt out of Stable you can set the base theme to false in
your theme's .info.yml file (see the warning below before doing this):
  base theme: false

Warning: Themes that opt out of using Stable as a base theme will need
continuous maintenance as core changes, so only opt out if you are prepared to
keep track of those changes and how they affect your theme.

ABOUT DRUPAL THEMING
--------------------

For more information, see Drupal.org's theming guide.
https://www.drupal.org/docs/8/theming
