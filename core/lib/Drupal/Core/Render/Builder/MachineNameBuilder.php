<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'machine_name' element.
 */
class MachineNameBuilder extends Textfield {

  protected $renderable = ['#type' => 'machine_name'];

  /**
   * Set the machine_name property on the machine_name.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMachineName($value) {
    $this->set('machine_name', $value);
    return $this;
  }

  /**
   * Set the exists property on the machine_name.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setExists($value) {
    $this->set('exists', $value);
    return $this;
  }

  /**
   * Set the source property on the machine_name.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSource($value) {
    $this->set('source', $value);
    return $this;
  }

  /**
   * Set the label property on the machine_name.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLabel($value) {
    $this->set('label', $value);
    return $this;
  }

  /**
   * Set the replace_pattern property on the machine_name.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setReplacePattern($value) {
    $this->set('replace_pattern', $value);
    return $this;
  }

  /**
   * Set the replace property on the machine_name.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setReplace($value) {
    $this->set('replace', $value);
    return $this;
  }

}
