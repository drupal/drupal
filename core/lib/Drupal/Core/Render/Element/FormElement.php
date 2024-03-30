<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for form element plugins.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. use
 *   \Drupal\Core\Render\Element\FormElementBase instead.
 *
 * @see https://www.drupal.org/node/3436275
 */
abstract class FormElement extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    @trigger_error('\Drupal\Core\Render\Element\FormElement is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   */
  public static function setAttributes(&$element, $class = []) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::setAttributes() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::setAttributes() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    parent::setAttributes($element, $class);
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderGroup($element) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::preRenderGroup() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::preRenderGroup() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::preRenderGroup($element);
  }

  /**
   * {@inheritdoc}
   */
  public static function processAjaxForm(&$element, FormStateInterface $form_state, &$complete_form) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::processAjaxForm() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::processAjaxForm() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::processAjaxForm($element, $form_state, $complete_form);
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderAjaxForm($element) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::preRenderAjaxForm() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::preRenderAjaxForm() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::preRenderAjaxForm($element);
  }

  /**
   * {@inheritdoc}
   */
  public static function processGroup(&$element, FormStateInterface $form_state, &$complete_form) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::processGroup() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::processGroup() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::processGroup($element, $form_state, $complete_form);
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::valueCallback() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::valueCallback() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::valueCallback($element, $input, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function processPattern(&$element, FormStateInterface $form_state, &$complete_form) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::processPattern() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::processPattern() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::processPattern($element, $form_state, $complete_form);
  }

  /**
   * {@inheritdoc}
   */
  public static function validatePattern(&$element, FormStateInterface $form_state, &$complete_form) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::validatePattern() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::validatePattern() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    parent::validatePattern($element, $form_state, $complete_form);
  }

  /**
   * {@inheritdoc}
   */
  public static function processAutocomplete(&$element, FormStateInterface $form_state, &$complete_form) {
    @trigger_error('\Drupal\Core\Render\Element\FormElement::processAutocomplete() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\FormElementBase::processAutocomplete() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::processAutocomplete($element, $form_state, $complete_form);
  }

}
