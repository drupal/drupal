
Introduction
------------
This folder contains a partial fork of jQuery UI 1.12.1. jQuery UI has been
marked as an emeritus project by the OpenJS foundation. Emeritus projects are
those which the maintainers feel have reached or are nearing end-of-life.

jQuery UI will potentially reach end-of-life before Drupal 9 does. In
preparation for this, Drupal has forked jQuery UI core and jQuery UI components
still used by Drupal core. This fork will make it easier to maintain jQuery UI's
code when necessary.

jQuery UI components used:
  * Autocomplete
  * Button
  * Checkboxradio
  * Controlgroup
  * Draggable
  * Dialog
  * Menu
  * Position
  * Resizable
  * Widget Factory

Development
-----------
Development on this fork of jQuery UI is limited to fixes for security issues
affecting Drupal projects.

Production versions of jQuery UI code can be generated with the following
commands:

Navigate to `core/` folder:
```
cd core/
```

Ensure that dependencies have been installed:
```
yarn install
```

Build jQuery UI files for production:
```
yarn run build:jqueryui
```

Note: at the moment our forked code doesn't have any test coverage. Making any
changes to the code should be avoided until
https://www.drupal.org/project/drupal/issues/3093172 has been resolved.

More information
----------------

 * See the Drupal.org issue that partially forked jQuery UI:
   https://www.drupal.org/project/drupal/issues/3087685

 * See the Drupal.org issue for removing the rest of the jQuery UI components:
   https://www.drupal.org/project/drupal/issues/3067261
