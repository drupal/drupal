<?php

namespace Drupal\Core\Render\Element;

abstract class TextElement extends FormElement {

  public function setPlaceholder($placeholder) {
    return $this->set('placeholder', $placeholder);
  }

  public function setMaxLength(int $max_length) {
    return $this->set('maxlength', $max_length);
  }

}
