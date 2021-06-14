<?php

namespace Drupal\jsonapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure JSON:API settings for this site.
 *
 * @internal
 */
class JsonApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jsonapi_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jsonapi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $jsonapi_config = $this->config('jsonapi.settings');

    $form['read_only'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allowed operations'),
      '#options' => [
        'r' => $this->t('Accept only JSON:API read operations.'),
        'rw' => $this->t('Accept all JSON:API create, read, update, and delete operations.'),
      ],
      '#default_value' => $jsonapi_config->get('read_only') === TRUE ? 'r' : 'rw',
      '#description' => $this->t('Warning: Only enable all operations if the site requires it. <a href=":docs">Learn more about securing your site with JSON:API.</a>', [':docs' => 'https://www.drupal.org/docs/8/modules/jsonapi/security-considerations']),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('jsonapi.settings')
      ->set('read_only', $form_state->getValue('read_only') === 'r')
      ->save();

    parent::submitForm($form, $form_state);
  }

}
