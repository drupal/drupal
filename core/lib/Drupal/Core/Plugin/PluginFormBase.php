<?php

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginAwareInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for plugin forms.
 *
 * Classes extending this can be in any namespace, but are commonly placed in
 * the 'PluginForm' namespace, such as \Drupal\module_name\PluginForm\ClassName.
 */
abstract class PluginFormBase implements PluginFormInterface, PluginAwareInterface {

  /**
   * The plugin this form is for.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setPlugin(PluginInspectionInterface $plugin) {
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

}
