Code in the Drupal\Core namespace represents Drupal Subsystems provided by the
base system.  These subsystems MAY depend on Drupal Components and other
Subsystems, but MAY NOT depend on any code in a module.

Each Subsystem should be in its own namespace, and should be as self-contained
as possible.
