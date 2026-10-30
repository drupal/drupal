# Drupal Metapackages

A metapackage is a Composer package that contains only a composer.json, and
has no other content. In other words, the purpose of a metapackage is to
provide dependencies, not to provide code or data.


## Metapackages Provided by Drupal Core

Drupal Core provides two metapackages.

 - drupal/core-recommended: This project pins to the exact version of each
   dependency used in drupal/core. It also requires drupal/core, so
   drupal/core-recommended should be used INSTEAD OF drupal/core. See usage
   diagram below. This relationship makes it easier for Composer to update
   a Drupal project.

 - drupal/core-dev: This project provides the same version constraints as Drupal
   uses for testing. It is useful for projects that either wish to run some of
   the Drupal tests directly, or for projects that may wish to use the same
   components that Drupal does for testing.


## Metapackage Usage in Template Projects

The relationship between the metapackages drupal/core-recommended and
drupal/core-dev and the project (subtree split) drupal/core, as used in the
drupal/recommended-project is shown below:

+----------------------------+
| drupal/recommended-project |
+----------------------------+
 |
 +--"require":
 |    |
 |    |   +-------------------------+   +-------------+
 |    +-->| drupal/core-recommended |-->| drupal/core |
 |        +-------------------------+   +-------------+
 |
 +--"require-dev":
      |
      |   +-------------------------+
      +-->| drupal/core-dev         |
          +-------------------------+

If a user does not wish to pin their Drupal project's dependencies to the same
versions used in drupal/core, then they should replace drupal/core-recommended
with drupal/core in their "require" section.

If a user does not need the testing dependencies in their Drupal project, then
they may simply remove drupal/core-dev from the "require-dev" section.
