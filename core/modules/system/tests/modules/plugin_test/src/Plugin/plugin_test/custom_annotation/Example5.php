<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\plugin_test\Plugin\Attribute\PluginExample;

/**
 * Provides a test plugin with a custom attribute.
 *
 * This plugin ensures that StaticReflectionParser::parse() correctly determines
 * the fully qualified attribute name.
 *
 * @see \Drupal\Component\Annotation\Doctrine\StaticReflectionParser::parse()
 */
#[\Attribute]
#[PluginExample(
  id: "example_5",
  custom: "Example 5"
)]
class Example5 {}
