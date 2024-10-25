# Why do we need the `updated_module` fixtures?
Because there is a need to thoroughly test the updating of a module. See `\Drupal\Tests\package_manager\Build\PackageUpdateTest`.

This requires 2 versions (`1.0.0` and `1.1.0`) of the same module (`updated_module`), each with a different code bases.

The test updates from one version to the next, and verifies the updated module's code base is actually used after the update: it verifies the updated logic of version of `\Drupal\updated_module\PostApplySubscriber` is being executed.

`\Drupal\fixture_manipulator\FixtureManipulator` cannot manipulate code nor does it modify the file system: it only creates a "skeleton" extension. (See `\Drupal\fixture_manipulator\FixtureManipulator::addProjectAtPath()`.)

# Why do we need the `alpha` fixtures?
To be able to test that `php-tuf/composer-stager` indeed only updates the package for which an update was requested (even though more updates are available), no fixture manipulation is allowed to occur. This requires updating a `path` composer package repository to first serve contain one version of a package, and then another. That is what these fixtures are used for.
