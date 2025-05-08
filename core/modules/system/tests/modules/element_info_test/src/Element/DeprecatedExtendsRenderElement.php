<?php

declare(strict_types=1);

namespace Drupal\element_info_test\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElement as RenderElementDeprecated;

// @phpcs:disable
/**
 * Provides render element that extends deprecated RenderElement for testing.
 */
#[RenderElement('deprecated_extends_render')]
// @phpstan-ignore class.extendsDeprecatedClass
class DeprecatedExtendsRenderElement extends RenderElementDeprecated {
// @phpcs:enable

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [];
  }

}
