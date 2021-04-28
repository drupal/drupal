<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'inline_template' element.
 */
class InlineTemplateBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'inline_template'];

  /**
   * Set the template property on the inline_template.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTemplate($value) {
    $this->set('template', $value);
    return $this;
  }

  /**
   * Set the context property on the inline_template.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setContext($value) {
    $this->set('context', $value);
    return $this;
  }

}
