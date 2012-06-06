INTRODUCTION
------------
This document describes how to:

  * Update your Drupal site from one minor 7.x version to another minor 7.x
    version; for example, from 7.8 to 7.9, or from 7.6 to 7.10.

  * Upgrade your Drupal site's major version from 6.x to 7.x.

First steps and definitions:

  * If you are upgrading to Drupal version x.y, then x is known as the major
    version number, and y is known as the minor version number. The download
    file will be named drupal-x.y.tar.gz (or drupal-x.y.zip).

  * All directories mentioned in this document are relative to the directory of
    your Drupal installation.

  * Make a full backup of all files, directories, and your database(s) before
    starting, and save it outside your Drupal installation directory.
    Instructions may be found at http://drupal.org/upgrade/backing-up-the-db

  * It is wise to try an update or upgrade on a test copy of your site before
    applying it to your live site. Even minor updates can cause your site's
    behavior to change.

  * Each new release of Drupal has release notes, which explain the changes made
    since the previous version and any special instructions needed to update or
    upgrade to the new version. You can find a link to the release notes for the
    version you are upgrading or updating to on the Drupal project page
    (http://drupal.org/project/drupal).

UPGRADE PROBLEMS
----------------
If you encounter errors during this process,

  * Note any error messages you see.

  * Restore your site to its previous state, using the file and database backups
    you created before you started the upgrade process. Do not attempt to do
    further upgrades on a site that had update problems.

  * Consult one of the support options listed on http://drupal.org/support

More in-depth information on upgrading can be found at http://drupal.org/upgrade

MINOR VERSION UPDATES
---------------------
To update from one minor 7.x version of Drupal to any later 7.x version, after
following the instructions in the INTRODUCTION section at the top of this file:

1. Log in as a user with the permission "Administer software updates".

2. Go to Administration > Configuration > Development > Maintenance mode.
   Enable the "Put site into maintenance mode" checkbox and save the
   configuration.

3. Remove all old core files and directories, except for the 'sites' directory
   and any custom files you added elsewhere.

   If you made modifications to files like .htaccess or robots.txt, you will
   need to re-apply them from your backup, after the new files are in place.

   Sometimes an update includes changes to default.settings.php (this will be
   noted in the release notes). If that's the case, follow these steps:

   - Make a backup copy of your settings.php file, with a different file name.

   - Make a copy of the new default.settings.php file, and name the copy
     settings.php (overwriting your previous settings.php file).

   - Copy the custom and site-specific entries from the backup you made into the
     new settings.php file. You will definitely need the lines giving the
     database information, and you will also want to copy in any other
     customizations you have added.

4. Download the latest Drupal 7.x release from http://drupal.org to a
   directory outside of your web root. Extract the archive and copy the files
   into your Drupal directory.

   On a typical Unix/Linux command line, use the following commands to download
   and extract:

     wget http://drupal.org/files/projects/drupal-x.y.tar.gz
     tar -zxvf drupal-x.y.tar.gz

   This creates a new directory drupal-x.y/ containing all Drupal files and
   directories. Copy the files into your Drupal installation directory:

     cp -R drupal-x.y/* drupal-x.y/.htaccess /path/to/your/installation

   If you do not have command line access to your server, download the archive
   from http://drupal.org using your web browser, extract it, and then use an
   FTP client to upload the files to your web root.

5. Re-apply any modifications to files such as .htaccess or robots.txt.

6. Run update.php by visiting http://www.example.com/update.php (replace
   www.example.com with your domain name). This will update the core database
   tables.

   If you are unable to access update.php do the following:

   - Open settings.php with a text editor.

   - Find the line that says:
     $update_free_access = FALSE;

   - Change it into:
     $update_free_access = TRUE;

   - Once the upgrade is done, $update_free_access must be reverted to FALSE.

7. Go to Administration > Reports > Status report. Verify that everything is
   working as expected.

8. Ensure that $update_free_access is FALSE in settings.php.

9. Go to Administration > Configuration > Development > Maintenance mode.
   Disable the "Put site into maintenance mode" checkbox and save the
   configuration.

MAJOR VERSION UPGRADE
---------------------
To upgrade from a previous major version of Drupal to Drupal 7.x, after
following the instructions in the INTRODUCTION section at the top of this file:

1. Check on the Drupal 7 status of your contributed and custom modules and
   themes. See http://drupal.org/node/948216 for information on upgrading
   contributed modules and themes. See http://drupal.org/node/895314 for a list
   of modules that have been moved into core for Drupal 7, and instructions on
   how to update them. See http://drupal.org/update/modules for information on
   how to update your custom modules, and http://drupal.org/update/theme for
   custom themes.

   You may decide at this point that you cannot upgrade your site, because
   needed modules or themes are not ready for Drupal 7.

2. Update to the latest available version of Drupal 6.x (if your current version
   is Drupal 5.x, you have to upgrade to 6.x first). If you need to update,
   download Drupal 6.x and follow the instructions in its UPGRADE.txt. This
   document only applies for upgrades from 6.x to 7.x.

3. In addition to updating to the latest available version of Drupal 6.x core,
   you must also upgrade all of your contributed modules for Drupal to their
   latest Drupal 6.x versions.

4. Log in as user ID 1 (the site maintenance user).

5. Go to Administer > Site configuration > Site maintenance. Select
   "Off-line" and save the configuration.

6. Go to Administer > Site building > Themes. Enable "Garland" and select it as
   the default theme.

7. Go to Administer > Site building > Modules. Disable all modules that are not
   listed under "Core - required" or "Core - optional". It is possible that some
   modules cannot be disabled, because others depend on them. Repeat this step
   until all non-core modules are disabled.

   If you know that you will not re-enable some modules for Drupal 7.x and you
   no longer need their data, then you can uninstall them under the Uninstall
   tab after disabling them.

8. On the command line or in your FTP client, remove the file

     sites/default/default.settings.php

9. Remove all old core files and directories, except for the 'sites' directory
   and any custom files you added elsewhere.

   If you made modifications to files like .htaccess or robots.txt, you will
   need to re-apply them from your backup, after the new files are in place.

10. If you uninstalled any modules, remove them from the sites/all/modules and
   other sites/*/modules directories. Leave other modules in place, even though
   they are incompatible with Drupal 7.x.

11. Download the latest Drupal 7.x release from http://drupal.org to a
   directory outside of your web root. Extract the archive and copy the files
   into your Drupal directory.

   On a typical Unix/Linux command line, use the following commands to download
   and extract:

     wget http://drupal.org/files/projects/drupal-x.y.tar.gz
     tar -zxvf drupal-x.y.tar.gz

   This creates a new directory drupal-x.y/ containing all Drupal files and
   directories. Copy the files into your Drupal installation directory:

     cp -R drupal-x.y/* drupal-x.y/.htaccess /path/to/your/installation

   If you do not have command line access to your server, download the archive
   from http://drupal.org using your web browser, extract it, and then use an
   FTP client to upload the files to your web root.

12. Re-apply any modifications to files such as .htaccess or robots.txt.

13. Make your settings.php file writeable, so that the update process can
   convert it to the format of Drupal 7.x. settings.php is usually located in

     sites/default/settings.php

14. Run update.php by visiting http://www.example.com/update.php (replace
   www.example.com with your domain name). This will update the core database
   tables.

   If you are unable to access update.php do the following:

   - Open settings.php with a text editor.

   - Find the line that says:
     $update_free_access = FALSE;

   - Change it into:
     $update_free_access = TRUE;

   - Once the upgrade is done, $update_free_access must be reverted to FALSE.

15. Backup your database after the core upgrade has run.

16. Replace and update your non-core modules and themes, following the
   procedures at http://drupal.org/node/948216

17. Go to Administration > Reports > Status report. Verify that everything is
   working as expected.

18. Ensure that $update_free_access is FALSE in settings.php.

19. Go to Administration > Configuration > Development > Maintenance mode.
   Disable the "Put site into maintenance mode" checkbox and save the
   configuration.

To get started with Drupal 7 administration, visit
http://drupal.org/getting-started/7/admin
