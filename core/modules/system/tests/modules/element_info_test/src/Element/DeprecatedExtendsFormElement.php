<?php

declare(strict_types=1);

namespace Drupal\element_info_test\Element;

use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\FormElement as FormElementDeprecated;

// @phpcs:disable
/**
 * Provides render element that extends deprecated FormElement for testing.
 */
#[FormElement('deprecated_extends_form')]
// @phpstan-ignore class.extendsDeprecatedClass
class DeprecatedExtendsFormElement extends FormElementDeprecated {
// @phpcs:enable

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [];
  }

}
