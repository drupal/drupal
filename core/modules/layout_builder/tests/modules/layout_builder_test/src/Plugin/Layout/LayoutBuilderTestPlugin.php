<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The Layout Builder Test Plugin.
 */
#[Layout(
  id: 'layout_builder_test_plugin',
  label: new TranslatableMarkup('Layout Builder Test Plugin'),
  regions: [
    "main" => [
      "label" => new TranslatableMarkup("Main Region"),
    ],
  ],
)]
class LayoutBuilderTestPlugin extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['main']['#attributes']['class'][] = 'go-birds';
    if ($this->inPreview) {
      $build['main']['#attributes']['class'][] = 'go-birds-preview';
    }
    return $build;
  }

}
