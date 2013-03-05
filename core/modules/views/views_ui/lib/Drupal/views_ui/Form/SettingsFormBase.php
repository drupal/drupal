<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\SettingsFormBase.
 */

namespace Drupal\views_ui\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\system\SystemConfigFormBase;

/**
 * Form builder for the advanced admin settings page.
 */
abstract class SettingsFormBase extends SystemConfigFormBase {

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

}
