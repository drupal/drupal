<?php

namespace Drupal\plugin_test\Plugin\plugin_test\fruit;

use Drupal\non_installed_module\Plugin\plugin_test\fruit\YummyFruit;

/**
 * @Plugin(
 *   id = "extending_non_installed_class",
 *   label = "A plugin whose class is extending from a non-installed module class",
 *   color = "pink",
 * )
 */
class ExtendingNonInstalledClass extends YummyFruit { }
