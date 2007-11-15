// $Id: README.txt,v 1.1 2007/11/15 23:12:38 goba Exp $

The files directory is the default file system path used to store
all uploaded files, as well as some temporary files created by Drupal. To
successfully install Drupal, the files directory must exist and be
writable by the web server process.

After installation, the settings for the file system path may be modified
to store uploaded files in a different location. Ensure that this new
location exists, is accessible, and is writable by the web server process.
The file system path settings can be accessed by selecting these menu items
from the Navigation menu:

  administer > site configuration > file system

You may wish to modify the file system path if:

  * your site runs multiple Drupal installations from a single codebase
    (modify the file system path of each installation to a different
    directory so that uploads do not overlap between installations);

  * your site runs a number of web server front-ends behind a load
    balancer or reverse proxy (modify the file system path on each
    server to point to a shared file repository); or,

  * your site policies specify that all site-related files are stored
    under the sites directory in order to simplify backup and restore
    operations (modify the file system path to point to a newly-created
    directory underneath sites).

Changing the file system path after files have been uploaded may cause
unexpected problems on an existing site. If you modify the file system path
on an existing site, remember to copy all files from the original location
to the new location.
