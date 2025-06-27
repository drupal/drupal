<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Render\Attribute\RenderElement;

/**
 * Provides a generic, empty element.
 *
 * Manually creating this element is not necessary; however, the system
 * often needs to convert render arrays that do not have a type. While
 * arrays without a #type are valid PHP code, it is not possible to create
 * an object without a class.
 */
#[RenderElement('generic')]
class Generic extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function setType(): void {
  }

}
