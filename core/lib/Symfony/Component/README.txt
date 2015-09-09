Symfony component interfaces included as part of the Drupal codebase are used to
provide Drupal-specific implementations of Symfony subsystems that are not
available as standalone components and thus cannot be cherry-picked.

A composer.json "replace" entry is used in order to bypass the composer.json
dependency onto the Symfony components originally including the interfaces
provided here.
