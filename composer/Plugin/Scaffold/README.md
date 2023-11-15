# Drupal Composer Scaffold

This project provides a composer plugin making the Drupal core Composer package
work correctly in a Composer project.

This takes care of:
  - Placing scaffold files (like `index.php`, `update.php`, …) from the
    `drupal/core` project into their desired location inside the web root. Only
    individual files may be scaffolded with this plugin.
  - Writing an autoload.php file to the web root, which includes the Composer
    autoload.php file.

The purpose of scaffolding files is to allow Drupal sites to be fully managed by
Composer, and still allow individual asset files to be placed in arbitrary
locations. The goal of doing this is to enable a properly configured composer
template to produce a file layout that exactly matches the file layout of a
Drupal 8.7.x and earlier tarball distribution. Other file layouts will also be
possible; for example, a project layout very similar to the current
[drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-scaffold)
template will also be provided. When one of these projects is used, the user
should be able to use `composer require` and `composer update` on a Drupal site
immediately after untarring the downloaded archive.

Note that the dependencies of a Drupal site are only able to scaffold files if
explicitly granted that right in the top-level composer.json file. See
[allowed packages](#allowed-packages), below.

## Usage

Drupal Composer Scaffold is used by requiring `drupal/core-composer-scaffold` in your
project, and providing configuration settings in the `extra` section of your
project's composer.json file. Additional configuration from the composer.json
file of your project's dependencies is also consulted in order to scaffold the
files a project needs. Additional information may be added to the beginning or
end of scaffold files, as is commonly done to `.htaccess` and `robots.txt`
files. See [altering scaffold files](#altering-scaffold-files) for more
information.

Typically, the scaffold operations run automatically as needed, e.g. after
`composer install`, so it is usually not necessary to do anything different
to scaffold a project once the configuration is set up in the project
composer.json file, as described below. To scaffold files directly, run:
```
composer drupal:scaffold
```

### Allowed Packages

Scaffold files are stored inside of projects that are required from the main
project's composer.json file as usual. The scaffolding operation happens after
`composer install`, and involves copying or symlinking the desired assets to
their destination location. In order to prevent arbitrary dependencies from
copying files via the scaffold mechanism, only those projects that are
specifically permitted by the top-level project will be used to scaffold files.

Example: Permit scaffolding from the project `upstream/project`
```
  "name": "my/project",
  ...
  "extra": {
    "drupal-scaffold": {
      "allowed-packages": [
        "upstream/project"
      ],
      ...
    }
  }
```
Allowing a package to scaffold files also permits it to delegate permission to
scaffold to any project that it requires itself. This allows a package to
organize its scaffold assets as it sees fit. For example, if `upstream/project`
stores its assets in a subproject `upstream/assets`, `upstream/assets` would
implicitly be allowed to scaffold files.

It is possible for a project to obtain scaffold files from multiple projects.
For example, a Drupal project using a distribution, and installing on a specific
web hosting service provider might take its scaffold files from:

- Drupal core
- Its distribution
- A project provided by the hosting provider
- The project itself

Each project allowed to scaffold by the top-level project will be used in turn,
with projects declared later in the `allowed-packages` list taking precedence
over the projects named before. `drupal/core` is implicitly allowed and will be
placed at the top of the list. The top-level composer.json itself is also
implicitly allowed to scaffold files, and its scaffold files have highest
priority.

### Defining Project Locations

The top-level project in turn must define where the web root is located. It does
so via the `locations` mapping, as shown below:
```
  "name": "my/project",
  ...
  "extra": {
    "drupal-scaffold": {
      "locations": {
        "web-root": "./docroot"
      },
      ...
    }
  }
```
This makes it possible to configure a project with different file layouts; for
example, either the `drupal/drupal` file layout or the
`drupal-composer/drupal-project` file layout could be used to set up a project.

If a web-root is not explicitly defined, then it will default to `.`, the same
directory as the composer.json file.

### Altering Scaffold Files

Sometimes, a project might wish to use a scaffold file provided by a dependency,
but alter it in some way. Two forms of alteration are supported: appending and
patching.

The example below shows a project that appends additional entries onto the end
of the `robots.txt` file provided by `drupal/core`:
```
  "name": "my/project",
  ...
  "extra": {
    "drupal-scaffold": {
      "file-mapping": {
        "[web-root]/robots.txt": {
          "append": "assets/my-robots-additions.txt",
        }
      }
    }
  }
```
It is also possible to prepend to a scaffold file instead of, or in addition to
appending by including a "prepend" entry that provides the relative path to the
file to prepend to the scaffold file.

The example below demonstrates the use of the `post-drupal-scaffold-cmd` hook
to patch the `.htaccess` file using a patch.
```
  "name": "my/project",
  ...
  "scripts": {
    "post-drupal-scaffold-cmd": [
      "cd docroot && patch -p1 <../patches/htaccess-ssl.patch"
    ]
  }
```

### Defining Scaffold Files

The placement of scaffold assets is under the control of the project that
provides them, but the location is always relative to some directory defined by
the root project -- usually the web root. For example, the scaffold file
`robots.txt` is copied from its source location, `assets/robots.txt` into the
web root in the snippet below.
```
{
  "name": "drupal/assets",
  ...
  "extra": {
    "drupal-scaffold": {
      "file-mapping": {
        "[web-root]/robots.txt": "assets/robots.txt",
        ...
      }
    }
  }
}
```

### Excluding Scaffold Files

Sometimes, a project might prefer to entirely replace a scaffold file provided
by a dependency, and receive no further updates for it. This can be done by
setting the value for the scaffold file to exclude to `false`:
```
  "name": "my/project",
  ...
  "extra": {
    "drupal-scaffold": {
      "file-mapping": {
        "[web-root]/robots.txt": false
      }
    }
  }
```
If possible, use the `append` and `prepend` directives as explained in [altering
scaffold files](#altering-scaffold-files), above. Excluding a file means that
your project will not get any bug fixes or other updates to files that are
modified locally.

### Overwrite

By default, scaffold files overwrite whatever content exists at the target
location. Sometimes a project may wish to provide the initial contents for a
file that will not be changed in subsequent updates. This can be done by setting
the `overwrite` flag to `false`, as shown in the example below:
```
{
  "name": "service-provider/d8-scaffold-files",
  "extra": {
    "drupal-scaffold": {
      "file-mapping": {
        "[web-root]/sites/default/settings.php": {
          "mode": "replace",
          "path": "assets/sites/default/settings.php",
          "overwrite": false
        }
      }
    }
  }
}
```
Note that the `overwrite` directive is intended to be used by starter kits,
service providers, and so on. Individual Drupal sites should exclude the file
by setting its value to false instead.

### Autoload File

The scaffold tool automatically creates the required `autoload.php` file at the
Drupal root as part of the scaffolding operation. This file should not be
modified or customized in any way. If it is committed to the repository, though,
then the scaffold tool will stop managing it. If the location of the `vendor`
directory is changed for any reason, and the `autoload.php` file has been
committed to the repository, manually delete it and then run `composer install`
to update it.

## Specifications

Reference section for the configuration directives for the "drupal-scaffold"
section of the "extra" section of a `composer.json` file appear below.

### allowed-packages

The `allowed-packages` configuration setting contains an ordered list of package
names that will be used during the scaffolding phase.
```
"allowed-packages": [
  "example/assets",
],
```
### file-mapping

The `file-mapping` configuration setting consists of a map from the destination
path of the file to scaffold to a set of properties that control how the file
should be scaffolded.

The available properties are as follows:

- mode: One of "replace", "append" or "skip".
- path: The path to the source file to write over the destination file.
- prepend: The path to the source file to prepend to the destination file, which
  must always be a scaffold file provided by some other project.
- append: Like `prepend`, but appends content rather than prepends.
- overwrite: If `false`, prevents a `replace` from happening if the destination
  already exists.

The mode may be inferred from the other properties. If the mode is not
specified, then the following defaults will be supplied:

- replace: Selected if a `path` property is present, or if the entry's value is
  a string rather than a property set.
- append: Selected if a `prepend` or `append` property is present.
- skip: Selected if the entry's value is a boolean `false`.

Examples:
```
"file-mapping": {
  "[web-root]/sites/default/default.settings.php": {
    "mode": "replace",
    "path": "assets/sites/default/default.settings.php",
    "overwrite": true
  },
  "[web-root]/sites/default/settings.php": {
    "mode": "replace",
    "path": "assets/sites/default/settings.php",
    "overwrite": false
  },
  "[web-root]/robots.txt": {
    "mode": "append",
    "prepend": "assets/robots-prequel.txt",
    "append": "assets/robots-append.txt"
  },
  "[web-root]/.htaccess": {
    "mode": "skip",
  }
}
```
The short-form of the above example would be:
```
"file-mapping": {
  "[web-root]/sites/default/default.settings.php": "assets/sites/default/default.settings.php",
  "[web-root]/sites/default/settings.php": {
    "path": "assets/sites/default/settings.php",
    "overwrite": false
  },
  "[web-root]/robots.txt": {
    "prepend": "assets/robots-prequel.txt",
    "append": "assets/robots-append.txt"
  },
  "[web-root]/.htaccess": false
}
```
Note that there is no distinct "prepend" mode; "append" mode is used to both
append and prepend to scaffold files. The reason for this is that scaffold file
entries are identified in the file-mapping section keyed by their destination
path, and it is not possible for multiple entries to have the same key. If
"prepend" were a separate mode, then it would not be possible to both prepend
and append to the same file.

By default, append operations may only be applied to files that were scaffolded
by a previously evaluated project. If the `force-append` attribute is added to
an `append` operation, though, then the append will be made to non-scaffolded
files if and only if the append text does not already appear in the file. When
using this mode, it is also possible to provide default contents to use in the
event that the destination file is entirely missing.

The example below demonstrates scaffolding a settings-custom.php file, and
including it from the existing `settings.php` file.

```
"file-mapping": {
  "[web-root]/sites/default/settings-custom.php": "assets/settings-custom.php",
  "[web-root]/sites/default/settings.php": {
    "append": "assets/include-settings-custom.txt",
    "force-append": true,
    "default": "assets/initial-default-settings.txt"
  }
}
```

Note that the example above still works if used with a project that scaffolds
the settings.php file.

### gitignore

The `gitignore` configuration setting controls whether or not this plugin will
manage `.gitignore` files for files written during the scaffold operation.

- true: `.gitignore` files will be updated when scaffold files are written.
- false: `.gitignore` files will never be modified.
- Not set: `.gitignore` files will be updated if the target directory is a local
working copy of a git repository, and the `vendor` directory is ignored
in that repository.

### locations

The `locations` configuration setting contains a list of named locations that
may be used in placing scaffold files. The only required location is `web-root`.
Other locations may also be defined if desired.
```
"locations": {
  "web-root": "./docroot"
},
```
### symlink

The `symlink` property causes `replace` operations to make a symlink to the
source file rather than copying it. This is useful when doing core development,
as the symlink files themselves should not be edited. Note that `append`
operations override the `symlink` option, to prevent the original scaffold
assets from being altered.
```
"symlink": true,
```
## Managing Scaffold Files

Scaffold files should be treated the same way that the `vendor` directory is
handled. If you need to commit `vendor` (e.g. in order to deploy your site),
then you should also commit your scaffold files. You should not commit your
`vendor` directory or scaffold files unless it is necessary.

If a dependency provides a scaffold file with `overwrite` set to `false`, that
file should be committed to your repository.

By default, `.gitignore` files will be automatically updated if needed when
scaffold files are written. See the `gitignore` setting in the Specifications
section above.

## Examples

Some full-length examples appear below.

Sample composer.json for a project that relies on packages that use composer-scaffold:
```
{
  "name": "my/project",
  "require": {
    "drupal/core-composer-scaffold": "*",
    "composer/installers": "^2.0",
    "cweagans/composer-patches": "^1.6.5",
    "drupal/core": "^8.8.x-dev",
    "service-provider/d8-scaffold-files": "^1"
  },
  "config": {
    "optimize-autoloader": true,
    "sort-packages": true
  },
  "extra": {
    "drupal-scaffold": {
      "locations": {
        "web-root": "./docroot"
      },
      "symlink": true,
      "file-mapping": {
        "[web-root]/.htaccess": false,
        "[web-root]/robots.txt": "assets/robots-default.txt"
      }
    }
  }
}
```

Sample composer.json for drupal/core, with assets placed in a different project:

```
{
  "name": "drupal/core",
  "extra": {
    "drupal-scaffold": {
      "allowed-packages": [
        "drupal/assets",
      ]
    }
  }
}
```

Sample composer.json for composer-scaffold files in drupal/assets:

```
{
  "name": "drupal/assets",
  "extra": {
    "drupal-scaffold": {
      "file-mapping": {
        "[web-root]/.csslintrc": "assets/.csslintrc",
        "[web-root]/.editorconfig": "assets/.editorconfig",
        "[web-root]/.eslintignore": "assets/.eslintignore",
        "[web-root]/.eslintrc.json": "assets/.eslintrc.json",
        "[web-root]/.gitattributes": "assets/.gitattributes",
        "[web-root]/.ht.router.php": "assets/.ht.router.php",
        "[web-root]/.htaccess": "assets/.htaccess",
        "[web-root]/sites/default/default.services.yml": "assets/default.services.yml",
        "[web-root]/sites/default/default.settings.php": "assets/default.settings.php",
        "[web-root]/sites/example.settings.local.php": "assets/example.settings.local.php",
        "[web-root]/sites/example.sites.php": "assets/example.sites.php",
        "[web-root]/index.php": "assets/index.php",
        "[web-root]/robots.txt": "assets/robots.txt",
        "[web-root]/update.php": "assets/update.php",
        "[web-root]/web.config": "assets/web.config"
      }
    }
  }
}
```

Sample composer.json for a library that implements composer-scaffold:

```
{
  "name": "service-provider/d8-scaffold-files",
  "extra": {
    "drupal-scaffold": {
      "file-mapping": {
        "[web-root]/sites/default/settings.php": "assets/sites/default/settings.php"
      }
    }
  }
}
```

Append to robots.txt:

```
{
  "name": "service-provider/d8-scaffold-files",
  "extra": {
    "drupal-scaffold": {
      "file-mapping": {
        "[web-root]/robots.txt": {
          "append": "assets/my-robots-additions.txt",
        }
      }
    }
  }
}
```

Patch a file after it's copied:

```
"post-drupal-scaffold-cmd": [
  "cd docroot && patch -p1 <../patches/htaccess-ssl.patch"
]
```

## Related Plugins

### drupal-composer/drupal-scaffold

Previous versions of Drupal Composer Scaffold (see community project,
[drupal-composer/drupal-scaffold](https://github.com/drupal-composer/drupal-project))
downloaded each scaffold file directly from its distribution server (e.g.
`https://git.drupalcode.org`) to the desired destination directory. This was
necessary, because there was no subtree split of the scaffold files available.
Copying the scaffold assets from projects already downloaded by Composer is more
effective, as downloading and unpacking archive files is more efficient than
downloading each scaffold file individually.

### composer/installers

The [composer/installers](https://github.com/composer/installers) plugin is
similar to this plugin in that it allows dependencies to be installed in
locations other than the `vendor` directory. However, Composer and the
`composer/installers` plugin have a limitation that one project cannot be moved
inside of another project. Therefore, if you use `composer/installers` to place
Drupal modules inside the directory `web/modules/contrib`, then you cannot also
use `composer/installers` to place files such as `index.php` and `robots.txt`
into the `web` directory. The drupal-scaffold plugin was created to work around
this limitation.
