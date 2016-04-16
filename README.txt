
CONTENTS OF THIS FILE
---------------------

 * About Drupal
 * Configuration and features
 * Installation profiles
 * Appearance
 * Developing for Drupal
 * More information

ABOUT DRUPAL
------------

Drupal is an open source content management platform supporting a variety of
websites ranging from personal weblogs to large community-driven websites. For
more information, see the Drupal website at https://www.drupal.org, and join
the Drupal community at https://www.drupal.org/community.

Legal information about Drupal:
 * Know your rights when using Drupal:
   See LICENSE.txt in the "core" directory.
 * Learn about the Drupal trademark and logo policy:
   https://www.drupal.com/trademark

CONFIGURATION AND FEATURES
--------------------------

Drupal core (what you get when you download and extract a drupal-x.y.tar.gz or
drupal-x.y.zip file from https://www.drupal.org/project/drupal) has what you
need to get started with your website. It includes several modules (extensions
that add functionality) for common website features, such as managing content,
user accounts, image uploading, and search. Core comes with many options that
allow site-specific configuration. In addition to the core modules, there are
thousands of contributed modules (for functionality not included with Drupal
core) available for download.

More about configuration:
 * Install, update, and maintain Drupal:
   See INSTALL.txt and UPDATE.txt in the "core" directory.
 * Learn about how to use Drupal to create your site:
   https://www.drupal.org/documentation
 * Follow best practices:
   https://www.drupal.org/best-practices
 * Download contributed modules to /modules to extend Drupal's functionality:
   https://www.drupal.org/project/modules
 * See also: "Developing for Drupal" for writing your own modules, below.


INSTALLATION PROFILES
---------------------

Installation profiles define additional steps (such as enabling modules,
defining content types, etc.) that run after the base installation provided
by core when Drupal is first installed. There are two basic installation
profiles provided with Drupal core.

Installation profiles from the Drupal community modify the installation process
to provide a website for a specific use case, such as a CMS for media
publishers, a web-based project tracking tool, or a full-fledged CRM for
non-profit organizations raising money and accepting donations. They can be
distributed as bare installation profiles or as "distributions". Distributions
include Drupal core, the installation profile, and all other required
extensions, such as contributed and custom modules, themes, and third-party
libraries. Bare installation profiles require you to download Drupal Core and
the required extensions separately; place the downloaded profile in the
/profiles directory before you start the installation process.

More about installation profiles and distributions:
 * Read about the difference between installation profiles and distributions:
   https://www.drupal.org/node/1089736
 * Download contributed installation profiles and distributions:
   https://www.drupal.org/project/distributions
 * Develop your own installation profile or distribution:
   https://www.drupal.org/developing/distributions


APPEARANCE
----------

In Drupal, the appearance of your site is set by the theme (themes are
extensions that set fonts, colors, and layout). Drupal core comes with several
themes. More themes are available for download, and you can also create your own
custom theme.

More about themes:
 * Download contributed themes to /themes to modify Drupal's appearance:
   https://www.drupal.org/project/themes
 * Develop your own theme:
   https://www.drupal.org/documentation/theme

DEVELOPING FOR DRUPAL
---------------------

Drupal contains an extensive API that allows you to add to and modify the
functionality of your site. The API consists of "hooks", which allow modules to
react to system events and customize Drupal's behavior, and functions that
standardize common operations such as database queries and form generation. The
flexible hook architecture means that you should never need to directly modify
the files that come with Drupal core to achieve the functionality you want;
instead, functionality modifications take the form of modules.

When you need new functionality for your Drupal site, search for existing
contributed modules. If you find a module that matches except for a bug or an
additional needed feature, change the module and contribute your improvements
back to the project in the form of a "patch". Create new custom modules only
when nothing existing comes close to what you need.

More about developing:
 * Search for existing contributed modules:
   https://www.drupal.org/project/modules
 * Contribute a patch:
   https://www.drupal.org/patch/submit
 * Develop your own module:
   https://www.drupal.org/developing/modules
 * Follow programming best practices:
   https://www.drupal.org/developing/best-practices
 * Refer to the API documentation:
   https://api.drupal.org/api/drupal/8
 * Learn from documented Drupal API examples:
   https://www.drupal.org/project/examples

MORE INFORMATION
----------------

 * See the Drupal.org online documentation:
   https://www.drupal.org/documentation

 * For a list of security announcements, see the "Security advisories" page at
   https://www.drupal.org/security (available as an RSS feed). This page also
   describes how to subscribe to these announcements via email.

 * For information about the Drupal security process, or to find out how to
   report a potential security issue to the Drupal security team, see the
   "Security team" page at https://www.drupal.org/security-team

 * For information about the wide range of available support options, visit
   https://www.drupal.org and click on Community and Support in the top or
   bottom navigation.
