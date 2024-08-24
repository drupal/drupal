<?php

declare(strict_types=1);

namespace Drupal\element_info_test\Element;

use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\FormElement as FormElementDeprecated;

/**
 * Provides render element that extends deprecated FormElement for testing.
 *
 * @phpstan-ignore class.extendsDeprecatedClass
 */
#[FormElement('deprecated_extends_form')]
class DeprecatedExtendsFormElement extends FormElementDeprecated {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [];
  }

}
