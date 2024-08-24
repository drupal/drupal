<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\plugin_test\Plugin\Attribute\PluginExample;

/**
 * Provides a test plugin with a custom attribute.
 */
#[/* comment */PluginExample(
  id: "example_3",
  custom: "George"
)]
class Example3 {}
