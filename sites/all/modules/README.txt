Place downloaded and custom modules that extend your site functionality beyond
Drupal core in this directory to ensure clean separation from core modules and
to facilitate safe, self-contained code updates. Contributed modules from the
Drupal community may be downloaded at http://drupal.org/project/modules.

It is safe to organize modules into subdirectories, such as "contrib" for
contributed modules, and "custom" for custom modules. Note that if you move a
module to a subdirectory after it has been enabled, you may need to clear the
Drupal cache so that it can be found.

In multisite configuration, modules found in this directory are available to
all sites. Alternatively, the sites/your_site_name/modules directory pattern may
be used to restrict modules to a specific site instance.

Refer to the "Developing for Drupal" section of the README.txt in the Drupal
root directory for further information on extending Drupal with custom modules.
