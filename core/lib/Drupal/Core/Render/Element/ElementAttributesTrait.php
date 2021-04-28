<?php

namespace Drupal\Core\Render\Element;

trait ElementAttributesTrait {

  public function setAttributes(array $attributes) {
    return $this->set('attributes', $attributes);
  }

}
