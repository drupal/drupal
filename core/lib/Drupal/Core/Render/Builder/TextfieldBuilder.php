<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'textfield' element.
 */
class TextfieldBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'textfield'];

  /**
   * Set the ajax property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAjax($value) {
    $this->set('ajax', $value);
    return $this;
  }

  /**
   * Set the attributes property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAttributes($value) {
    $this->set('attributes', $value);
    return $this;
  }

  /**
   * Set the autocomplete_path property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAutocompletePath($value) {
    $this->set('autocomplete_path', $value);
    return $this;
  }

  /**
   * Set the default_value property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setDefaultValue($value) {
    $this->set('default_value', $value);
    return $this;
  }

  /**
   * Set the description property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setDescription($value) {
    $this->set('description', $value);
    return $this;
  }

  /**
   * Set the disabled property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setDisabled($value) {
    $this->set('disabled', $value);
    return $this;
  }

  /**
   * Set the field_prefix property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setFieldPrefix($value) {
    $this->set('field_prefix', $value);
    return $this;
  }

  /**
   * Set the field_suffix property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setFieldSuffix($value) {
    $this->set('field_suffix', $value);
    return $this;
  }

  /**
   * Set the maxlength property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMaxlength($value) {
    $this->set('maxlength', $value);
    return $this;
  }

  /**
   * Set the placeholder property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setPlaceholder($value) {
    $this->set('placeholder', $value);
    return $this;
  }

  /**
   * Set the required property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setRequired($value) {
    $this->set('required', $value);
    return $this;
  }

  /**
   * Set the size property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSize($value) {
    $this->set('size', $value);
    return $this;
  }

  /**
   * Set the text_format property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTextFormat($value) {
    $this->set('text_format', $value);
    return $this;
  }

  /**
   * Set the title property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitle($value) {
    $this->set('title', $value);
    return $this;
  }

  /**
   * Set the title_display property on the textfield.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitleDisplay($value) {
    $this->set('title_display', $value);
    return $this;
  }

}
