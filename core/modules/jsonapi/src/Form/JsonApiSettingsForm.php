<?php

namespace Drupal\jsonapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
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
    $form['read_only'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allowed operations'),
      '#options' => [
        1 => $this->t('Accept only JSON:API read operations.'),
        0 => $this->t('Accept all JSON:API create, read, update, and delete operations.'),
      ],
      '#config_target' => new ConfigTarget(
        'jsonapi.settings',
        'read_only',
        // Convert the value to an integer when displaying the config value in
        // the form.
        'intval',
        // Convert the submitted value to a boolean before storing it in config.
        'boolval',
      ),
      '#description' => $this->t('Warning: Only enable all operations if the site requires it. <a href=":docs">Learn more about securing your site with JSON:API.</a>', [':docs' => 'https://www.drupal.org/docs/8/modules/jsonapi/security-considerations']),
    ];

    return parent::buildForm($form, $form_state);
  }

}
