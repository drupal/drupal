<?php

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
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove in Drupal 9.0.0.
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 8.3.x and will be removed in 9.0.0.', E_USER_DEPRECATED);
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove in Drupal 9.0.0.
   */
  public function settingsFormValidate(array $form, FormStateInterface $form_state) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 8.3.x and will be removed in 9.0.0.', E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove in Drupal 9.0.0.
   */
  public function settingsFormSubmit(array $form, FormStateInterface $form_state) {
    @trigger_error('The ' . __METHOD__ . ' method is deprecated since version 8.3.x and will be removed in 9.0.0.', E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $this->settingsForm($form, $form_state, $form_state->get('editor'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    return $this->settingsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    return $this->settingsFormSubmit($form, $form_state);
  }

}
