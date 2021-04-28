<?php

namespace Drupal\Core\Render\Element;

abstract class PlaceholderFormElement extends FormElement {

  public function setPlaceholder($placeholder) {
    return $this->set('placeholder', $placeholder);
  }

}
