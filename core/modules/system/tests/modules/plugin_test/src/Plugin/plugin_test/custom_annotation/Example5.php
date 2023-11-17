<?php

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

/**
 * Provides a test plugin with a custom attribute.
 *
 * This plugin ensures that StaticReflectionParser::parse() correctly determines
 * the fully qualified attribute name.
 *
 * @see \Drupal\Component\Annotation\Doctrine\StaticReflectionParser::parse()
 */
#[\Attribute]
#[\Drupal\plugin_test\Plugin\Attribute\PluginExample(
  id: "example_5",
  custom: "Example 5"
)]
class Example5 {}
