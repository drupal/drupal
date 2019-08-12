<?php

namespace Drupal\editor\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Plugin\PluginBase;

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
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (method_exists($this, 'settingsForm')) {
      @trigger_error(get_called_class() . "::settingsForm is deprecated since version 8.3.x. Rename the implementation 'buildConfigurationForm'. See https://www.drupal.org/node/2819753", E_USER_DEPRECATED);
      if ($form_state instanceof SubformStateInterface) {
        $form_state = $form_state->getCompleteFormState();
      }
      return $this->settingsForm($form, $form_state, $form_state->get('editor'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (method_exists($this, 'settingsFormValidate')) {
      @trigger_error(get_called_class() . "::settingsFormValidate is deprecated since version 8.3.x. Rename the implementation 'validateConfigurationForm'. See https://www.drupal.org/node/2819753", E_USER_DEPRECATED);
      if ($form_state instanceof SubformStateInterface) {
        $form_state = $form_state->getCompleteFormState();
      }
      $this->settingsFormValidate($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (method_exists($this, 'settingsFormSubmit')) {
      @trigger_error(get_called_class() . "::settingsFormSubmit is deprecated since version 8.3.x. Rename the implementation 'submitConfigurationForm'. See https://www.drupal.org/node/2819753", E_USER_DEPRECATED);
      if ($form_state instanceof SubformStateInterface) {
        $form_state = $form_state->getCompleteFormState();
      }
      $this->settingsFormSubmit($form, $form_state);
    }
  }

}
