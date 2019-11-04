<?php

namespace Drupal\media_library\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for configuring the Media Library module.
 *
 * @internal
 *   Form classes are internal.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['media_library.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_library_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['advanced_ui'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable advanced UI'),
      '#default_value' => $this->config('media_library.settings')->get('advanced_ui'),
      '#description' => $this->t('If checked, users creating new media items in the media library will see a summary of their selected media items, and they will be able insert their selection directly into the media field or text editor.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('media_library.settings')
      ->set('advanced_ui', (bool) $form_state->getValue('advanced_ui'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
