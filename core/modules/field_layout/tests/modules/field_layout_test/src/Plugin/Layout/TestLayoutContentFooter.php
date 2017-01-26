<?php

namespace Drupal\field_layout_test\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;

/**
 * Provides an annotated layout plugin for field_layout tests.
 *
 * @Layout(
 *   id = "test_layout_content_and_footer",
 *   label = @Translation("Test plugin: Content and Footer"),
 *   category = @Translation("Layout test"),
 *   description = @Translation("Test layout"),
 *   regions = {
 *     "content" = {
 *       "label" = @Translation("Content Region")
 *     },
 *     "footer" = {
 *       "label" = @Translation("Footer Region")
 *     }
 *   },
 * )
 */
class TestLayoutContentFooter extends LayoutDefault {

}
