Drupal Components are independent libraries that do not depend on the rest of
Drupal in order to function.

Components MAY depend on other Drupal Components or external libraries/packages,
but MUST NOT depend on any other Drupal code.

In other words, only dependencies that can be specified in a composer.json file
of the Component are acceptable dependencies.  Every Drupal Component presents a
valid dependency, because it is assumed to contain a composer.json file (even
if it may not exist yet).

Each Component should be in its own namespace, and should be as self-contained
as possible.  It should be possible to split a Component off to its own
repository and use as a stand-alone library, independently of Drupal.
