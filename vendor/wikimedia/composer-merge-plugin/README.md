[![Latest Stable Version](https://img.shields.io/packagist/v/wikimedia/composer-merge-plugin.svg?style=flat)](https://packagist.org/packages/wikimedia/composer-merge-plugin) [![License](https://img.shields.io/packagist/l/wikimedia/composer-merge-plugin.svg?style=flat)](https://github.com/wikimedia/composer-merge-plugin/blob/master/LICENSE)
[![Build Status](https://img.shields.io/travis/wikimedia/composer-merge-plugin.svg?style=flat)](https://travis-ci.org/wikimedia/composer-merge-plugin)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/wikimedia/composer-merge-plugin/master.svg?style=flat)](https://scrutinizer-ci.com/g/wikimedia/composer-merge-plugin/?branch=master)

Composer Merge Plugin
=====================

Merge one or more additional composer.json files at [Composer] runtime.

Composer Merge Plugin is intended to allow easier dependency management for
applications which ship a composer.json file and expect some deployments to
install additional Composer managed libraries. It does this by allowing the
application's top level `composer.json` file to provide a list of optional
additional configuration files. When Composer is run it will parse these files
and merge their configuration into the base configuration. This combined
configuration will allow downloading additional libraries and generating the
autoloader. It was specifically created to help with installation of
[MediaWiki] which has core Composer managed library requirements and optional
libraries and extensions which may also be managed via Composer.


Installation
------------
```
$ composer require wikimedia/composer-merge-plugin
```


Usage
-----

```
{
    "require": {
        "wikimedia/composer-merge-plugin": "dev-master"
    },
    "extra": {
        "merge-plugin": {
            "include": [
                "composer.local.json",
                "extensions/*/composer.json"
            ],
            "recurse": false,
            "replace": false,
            "merge-extra": false
        }
    }
}
```

The `include` key can specify either a single value or an array of values.
Each value is treated as a `glob()` pattern identifying additional
composer.json style configuration files to merge into the configuration for
the current Composer execution. By default the merge plugin is recursive, if
an included file also has a "merge-plugin" section it will also be processed.
This functionality can be disabled by setting `"recurse": false` inside the
"merge-plugin" section.

These sections of the found configuration files will be merged into the root
package configuration as though they were directly included in the top-level
composer.json file:

* [autoload](https://getcomposer.org/doc/04-schema.md#autoload)
* [autoload-dev](https://getcomposer.org/doc/04-schema.md#autoload-dev)
* [conflict](https://getcomposer.org/doc/04-schema.md#conflict)
* [provide](https://getcomposer.org/doc/04-schema.md#provide)
* [replace](https://getcomposer.org/doc/04-schema.md#replace)
* [repositories](https://getcomposer.org/doc/04-schema.md#repositories)
* [require](https://getcomposer.org/doc/04-schema.md#require)
* [require-dev](https://getcomposer.org/doc/04-schema.md#require-dev)
* [suggest](https://getcomposer.org/doc/04-schema.md#suggest)

A `"merge-extra": true` setting enables the merging of the "extra" section of
included files as well. The normal merge mode for the extra section is to
accept the first version of any key found (e.g. a key in the master config
wins over the version found in an imported config). If `replace` mode is
active (see below) then this behavior changes and the last found key will win
(the key in the master config is replaced by the key in the imported config).
Note that the `merge-plugin` key itself is excluded from this merge process.
Your mileage with merging the extra section will vary depending on the plugins
being used and the order in which they are processed by Composer.

By default, Composer's normal conflict resolution engine is used to determine
which version of a package should be installed if multiple files specify the
same package. A `"replace": true` setting can be provided inside the
"merge-plugin" section to change to a "last version specified wins" conflict
resolution strategy. In this mode, duplicate package declarations in merged
files will overwrite the declarations made in earlier files. Files are loaded
in the order specified in the `include` section with globbed files being
loaded in alphabetical order.


Running tests
-------------
```
$ composer install
$ composer test
```


Contributing
------------
Bug, feature requests and other issues should be reported to the [GitHub
project]. We accept code and documentation contributions via Pull Requests on
GitHub as well.

- [PSR-2 Coding Standard][] is used by the project. The included test
  configuration uses [PHP Code Sniffer][] to validate the conventions.
- Tests are encouraged. Our test coverage isn't perfect but we'd like it to
  get better rather than worse, so please try to include tests with your
  changes.
- Keep the documentation up to date. Make sure `README.md` and other
  relevant documentation is kept up to date with your changes.
- One pull request per feature. Try to keep your changes focused on solving
  a single problem. This will make it easier for us to review the change and
  easier for you to make sure you have updated the necessary tests and
  documentation.


License
-------
Composer Merge plugin is licensed under the MIT license. See the `LICENSE`
file for more details.


---
[Composer]: https://getcomposer.org/
[MediaWiki]: https://www.mediawiki.org/wiki/MediaWiki
[GitHub project]: https://github.com/wikimedia/composer-merge-plugin
[PSR-2 Coding Standard]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PHP Code Sniffer]: http://pear.php.net/package/PHP_CodeSniffer
