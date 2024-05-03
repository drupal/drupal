<?php

namespace Drupal\field_layout_test\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a Layout plugin for field_layout tests.
 */
#[Layout(
  id: 'test_layout_content_and_footer',
  label: new TranslatableMarkup('Test plugin: Content and Footer'),
  category: new TranslatableMarkup('Layout test'),
  description: new TranslatableMarkup('Test layout'),
  regions: [
    "content" => [
      "label" => new TranslatableMarkup("Content Region"),
    ],
    "footer" => [
      "label" => new TranslatableMarkup("Footer Region"),
    ],
  ],
)]
class TestLayoutContentFooter extends LayoutDefault {

}
