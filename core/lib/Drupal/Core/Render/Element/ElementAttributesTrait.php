<?php

namespace Drupal\Core\Render\Element;

trait ElementAttributesTrait {

  public function setAttribute(string $name, $value) {
    $this->renderable['#attributes'][$name] = $value;
    return $this;
  }

}
