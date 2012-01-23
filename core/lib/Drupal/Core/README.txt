Code in the Core namespace represents Drupal subsystems provided by the base
system.  These subsystems MAY depend on Drupal Components and other Subsystems,
but MAY NOT depend on any code in a module.

Each Component should be in its own namespace, and should be as self-contained
as possible.
