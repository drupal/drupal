Drupal Components are independent libraries that do not depend on the rest of
Drupal in order to function.  Components MAY depend on other Components, but
that is discouraged. Components MAY NOT depend on any code that is not part of
PHP itself or another Drupal Component.

Each Component should be in its own namespace, and should be as self-contained
as possible.  It should be possible to split a Component off to its own
repository and use as a stand-alone library, independently of Drupal.
