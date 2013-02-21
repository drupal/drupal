<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\SettingsFormBase.
 */

namespace Drupal\views_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Form builder for the advanced admin settings page.
 */
abstract class SettingsFormBase implements FormInterface {

  /**
   * Stores the views configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a \Drupal\views_ui\Form\SettingsFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for the temp store object.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->config = $config_factory->get('views.settings');
  }

  /**
   * Creates a new instance of this form.
   *
   * @return array
   *   The built form array.
   */
  public function getForm() {
    return drupal_get_form($this);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

}
