<?php

declare(strict_types=1);

namespace Drupal\plugin_test_extended\Plugin;

/**
 * This is an interface to be implemented by plugin classes in other modules.
 *
 * This is used to test that other modules' plugin classes implementing this
 * interface will not be discovered unless plugin_test_extended is installed.
 */
interface TestExternalDependencyInterface {}
