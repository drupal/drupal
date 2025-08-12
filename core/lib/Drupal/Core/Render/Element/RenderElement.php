<?php

namespace Drupal\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for render element plugins.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use
 *   \Drupal\Core\Render\Element\RenderElementBase instead.
 *
 * @see https://www.drupal.org/node/3436275
 */
abstract class RenderElement extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    @trigger_error('\Drupal\Core\Render\Element\RenderElement is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\RenderElementBase instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   */
  public static function setAttributes(&$element, $class = []) {
    @trigger_error('\Drupal\Core\Render\Element\RenderElement::setAttributes() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\RenderElementBase::setAttributes() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    parent::setAttributes($element, $class);
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderGroup($element) {
    @trigger_error('\Drupal\Core\Render\Element\RenderElement::preRenderGroup() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\RenderElementBase::preRenderGroup() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::preRenderGroup($element);
  }

  /**
   * {@inheritdoc}
   */
  public static function processAjaxForm(&$element, FormStateInterface $form_state, &$complete_form) {
    @trigger_error('\Drupal\Core\Render\Element\RenderElement::processAjaxForm() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\RenderElementBase::processAjaxForm() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::processAjaxForm($element, $form_state, $complete_form);
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderAjaxForm($element) {
    @trigger_error('\Drupal\Core\Render\Element\RenderElement::preRenderAjaxForm() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\RenderElementBase::preRenderAjaxForm() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::preRenderAjaxForm($element);
  }

  /**
   * {@inheritdoc}
   */
  public static function processGroup(&$element, FormStateInterface $form_state, &$complete_form) {
    @trigger_error('\Drupal\Core\Render\Element\RenderElement::processGroup() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\Render\Element\RenderElementBase::processGroup() instead. See https://www.drupal.org/node/3436275', E_USER_DEPRECATED);
    return parent::processGroup($element, $form_state, $complete_form);
  }

}
