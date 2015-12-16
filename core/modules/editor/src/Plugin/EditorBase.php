<?php

/**
 * @file
 * Contains \Drupal\editor\Plugin\EditorBase.
 */

namespace Drupal\editor\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines a base class from which other modules providing editors may extend.
 *
 * This class provides default implementations of the EditorPluginInterface so
 * that classes extending this one do not need to implement every method.
 *
 * Plugins extending this class need to specify an annotation containing the
 * plugin definition so the plugin can be discovered.
 *
 * @see \Drupal\editor\Annotation\Editor
 * @see \Drupal\editor\Plugin\EditorPluginInterface
 * @see \Drupal\editor\Plugin\EditorManager
 * @see plugin_api
 */
abstract class EditorBase extends PluginBase implements EditorPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getDefaultSettings() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit(array $form, FormStateInterface $form_state) {
  }

}
