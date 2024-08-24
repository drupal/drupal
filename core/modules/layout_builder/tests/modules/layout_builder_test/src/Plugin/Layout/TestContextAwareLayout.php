<?php

declare(strict_types=1);

namespace Drupal\layout_builder_test\Plugin\Layout;

use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The TestContextAwareLayout Class.
 */
#[Layout(
  id: 'layout_builder_test_context_aware',
  label: new TranslatableMarkup('Layout Builder Test: Context Aware'),
  regions: [
    "main" => [
      "label" => new TranslatableMarkup("Main Region"),
    ],
  ],
  context_definitions: [
    "user" => new EntityContextDefinition("entity:user"),
  ],
)]
class TestContextAwareLayout extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $build['main']['#attributes']['class'][] = 'user--' . $this->getContextValue('user')->getAccountName();
    return $build;
  }

}
