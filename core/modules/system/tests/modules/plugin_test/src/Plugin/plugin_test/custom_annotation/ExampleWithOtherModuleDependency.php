<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\plugin_test\Plugin\Attribute\PluginExample;
use Drupal\plugin_test_extended\Plugin\TestExternalDependencyInterface;

/**
 * Test class with a dependency on another module.
 */
#[PluginExample(
  id: 'example_with_other_module_dependency',
  custom: 'Example with other module dependency.'
)]
class ExampleWithOtherModuleDependency implements TestExternalDependencyInterface {}
