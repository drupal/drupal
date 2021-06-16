Composer-Ready Project Templates
================================

Thanks for using these Drupal project templates.

You can participate in its development on Drupal.org, through our issue system:
https://www.drupal.org/project/issues/drupal

You can get the full Drupal repo here:
https://www.drupal.org/project/drupal/git-instructions

You can browse the full Drupal repo here:
https://git.drupalcode.org/drupal

What does it do?
----------------

These project templates serve as a starting point for creating a
Composer-managed Drupal site. Once you have selected a template project and
created your own site, you will take ownership of your new project files.
Thereafter, future updates will be done with Composer.

There are two project templates to choose from:

1) drupal/recommended-project: The recommended project creates a new Drupal site
with a "relocated document root". This means that the files "index.php" and the
"core" directory and so on are placed inside a subfolder named "web" rather than
being placed next to "composer.json" and the "vendor" directory at the project
root. This layout is recommended because it allows you to configure your web
server to only provide access to files inside the "web" directory. Keeping the
vendor directory outside of the web server's document root is better for
security.

2) drupal/legacy-project: The legacy project creates a new Drupal site that has
the same layout used in Drupal 8.7.x and earlier. The files "index.php", the
"core" directory and so on are placed directly at the project root next to
"composer.json" and the "vendor" directory. The Vendor Hardening plugin is used
to ensure the security of this configuration for the Apache and Microsoft IIS
web servers. Use the legacy project layout only if there is some reason why you
cannot use the recommended project layout.


How do I set it up?
-------------------

Use Composer to create a new project using the desired starter template:

    composer -n create-project drupal/recommended-project my-project

Add new modules and themes with `composer require`:

    composer require drupal/devel:^1

All of your modules and themes can be updated along with Drupal core via:

    composer update

To update only Drupal core without any modules or themes, use:

    composer update drupal/core-recommended --with-dependencies

These template projects use drupal/core-composer-scaffold to place the scaffold
files. This plugin allows the top-level composer.json file for a Drupal site to
transform the scaffold files in different ways, e.g. to append new entries to
the end of robots.txt and so on. For documentation on how scaffolding works, see
https://git.drupalcode.org/project/drupal/tree/8.8.x/composer/Plugin/Scaffold
