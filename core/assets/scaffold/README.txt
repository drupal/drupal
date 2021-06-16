Drupal Scaffold Files are files that are contained inside drupal/core, but are
installed outside of the core directory (e.g. at the Drupal root).

Scaffold files were added to drupal/core in Drupal 8.8.x. During the Drupal 8
development cycle, the scaffold files are also being maintained in their original
locations. This is done so that Drupal sites based on the template project
drupal-composer/drupal-project may continue to download these files from the same
URLs they have historically been found at.

The scaffold files will be deleted from their original location in Drupal 9.
See https://www.drupal.org/project/drupal/issues/3075954 for follow-on work.
