<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'language_select' element.
 */
class LanguageSelectBuilder extends FormElementBuilderBase {

  protected $renderable = ['#type' => 'language_select'];

  /**
   * Set the ajax property on the language_select.
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
   * Set the attributes property on the language_select.
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
   * Set the default_value property on the language_select.
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
   * Set the description property on the language_select.
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
   * Set the disabled property on the language_select.
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
   * Set the empty_option property on the language_select.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setEmptyOption($value) {
    $this->set('empty_option', $value);
    return $this;
  }

  /**
   * Set the empty_value property on the language_select.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setEmptyValue($value) {
    $this->set('empty_value', $value);
    return $this;
  }

  /**
   * Set the field_prefix property on the language_select.
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
   * Set the field_suffix property on the language_select.
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
   * Set the languages property on the language_select.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLanguages($value) {
    $this->set('languages', $value);
    return $this;
  }

  /**
   * Set the multiple property on the language_select.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setMultiple($value) {
    $this->set('multiple', $value);
    return $this;
  }

  /**
   * Set the options property on the language_select.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setOptions($value) {
    $this->set('options', $value);
    return $this;
  }

  /**
   * Set the required property on the language_select.
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
   * Set the size property on the language_select.
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
   * Set the title property on the language_select.
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
   * Set the title_display property on the language_select.
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
