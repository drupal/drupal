<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\custom_annotation;

use Drupal\plugin_test\Plugin\Attribute\PluginExample;

/**
 * Provides a test plugin with an annotation and attribute.
 *
 * This is used to test discovery will pick up attributes over annotations.
 *
 * @PluginExample(
 *   id = "example_annotation_not_attribute"
 * )
 */
#[PluginExample('example_attribute_not_annotation')]
class ExampleWithAttributeAndAnnotation {}
