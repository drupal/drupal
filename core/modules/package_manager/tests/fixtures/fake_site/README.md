This directory is used as the basis for quasi-functional tests of Package Manager based on `\Drupal\Tests\package_manager\Kernel\PackageManagerKernelTestBase`. It provides a bare-bones simulation of a real Drupal site layout, including:

* A `.git` directory and `.gitignore` file
* A Drupal core directory with npm modules installed
* An `example` contrib module with its own `.git` directory and npm modules
* A directory in which to store private files (`private`)
* A default site directory with site-specific config files, as well as default versions of them
* A "real" site directory (`example.com`), with a public `files` directory, site-specific config files, and a SQLite database
* A `simpletest` directory containing artifacts from automated tests
* A `vendor` directory to contain installed Composer dependencies
* `composer.json` and `composer.lock` files

Tests which use this mock site will clone it into a temporary location, then run real Composer commands in it, along with other Package Manager operations, and make assertions about the results. It's important to understand that this mock site is not at all bootable or usable as a real Drupal site. But as far as Package Manager and Composer are concerned, it IS a completely valid project that can go through all phases of the stage life cycle.

The files named `ignore.txt` are named that way because Package Manager should ALWAYS ignore them when creating a staged copy of this mock site -- that is, they should never be copied into the stage directory, or removed from their original place, by Package Manager.

The `.git` directories are named `_git` because we cannot commit `.git` directories to our git repository. When a test clones this mock site, these directories are automatically renamed to `.git` in the copy.

This fixture can be re-created at any time by running, from the repository root, `php scripts/PackageManagerFixtureCreator.php`.
