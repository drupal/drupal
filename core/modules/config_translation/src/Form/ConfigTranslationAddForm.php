<?php

namespace Drupal\config_translation\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a form for adding configuration translations.
 *
 * @internal
 */
class ConfigTranslationAddForm extends ConfigTranslationFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_translation_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, RouteMatchInterface $route_match = NULL, $plugin_id = NULL, $langcode = NULL) {
    $form = parent::buildForm($form, $form_state, $route_match, $plugin_id, $langcode);
    $form['#title'] = $this->t('Add @language translation for %label', [
      '%label' => $this->mapper->getTitle(),
      '@language' => $this->language->getName(),
    ]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Successfully saved @language translation.', ['@language' => $this->language->getName()]));
  }

}
