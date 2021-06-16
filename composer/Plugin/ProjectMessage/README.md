The Drupal Project Message Plugin
=================================

Thanks for using this Drupal component.

You can participate in its development on Drupal.org, through our issue system:
https://www.drupal.org/project/issues/drupal

You can get the full Drupal repo here:
https://www.drupal.org/project/drupal/git-instructions

You can browse the full Drupal repo here:
https://git.drupalcode.org/project/drupal

What does it do?
----------------

This Composer plugin displays a configurable message after Composer installation
processes have finished.

This is ideal for a 'next steps' type prompt to help get the user oriented.

Currently only two Composer events are supported:
- post-create-project-cmd, when a `composer create-project` command has
  finished.
- post-install-cmd, when a `composer install` command has finished.

How do I set it up?
-------------------

Require this Composer plugin in your project template composer.json file:

    "require": {
      "drupal/core-project-message": "^8.8"
    }

### Configuration

There are three ways to configure this plugin to output information:
- Using a text file.
- Using composer.json schema keys.
- Embedding the information in the extra section of the composer.json file.

### Using a text file

By default, the plugin will respond to `post-install-cmd` or
`post-create-project-cmd` Composer events by looking for a similarly-named file
in the root of the project. For instance, if the user issues a `composer
create-project` command, when that command is finished, the plugin will look for
a file named `post-create-project-cmd-message.txt` and then display it on the
command line.

The file should be plain text, with markup suitable for Symfony's
`OutputInterface::writeln()` method. See documentation here:
https://symfony.com/doc/3.4/console/coloring.html

You can also configure your own file(s), using the `extra` section of your
composer.json file:

    "extra": {
      "drupal-core-project-message": {
        "post-create-project-cmd-file": "bespoke/special_file.txt"
      }
    }

### Using composer.json schema keys

You can tell the plugin to output the structured support information from the
composer.json file by telling it the keys you wish to display.

Currently, only `name`, `description`, `homepage` and `support` are supported.

    "extra": {
        "drupal-core-project-message": {
            "include-keys": ["homepage", "support"],
        }
    }

Then you can include this information in your composer.json file, which you
should probably be doing anyway.

### Embedding the information in the extra section

You can specify text directly within the `extra` section by using the
`[event-name]-message` key. This message should be an array, with one string for
each line:

    "extra": {
      "drupal-core-project-message": {
        "post-create-project-cmd-message": [
          "Thanks for installing this project.",
          "Please visit our documentation here: http://example.com/docs"
        ]
      }
    }

These strings should be plain text, with markup suitable for Symfony's
`OutputInterface::writeln()` method. See documentation here:
https://symfony.com/doc/3.4/console/coloring.html

The `-message` section will always override `-file` for a given event.
