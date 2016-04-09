<?php

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ResultRow;

/**
 * Defines a field handler to translate a language into its readable form.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("language")
 */
class LanguageField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['native_language'] = array('default' => FALSE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['native_language'] = array(
      '#title' => $this->t('Display in native language'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['native_language'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    $languages = $this->options['native_language'] ? \Drupal::languageManager()->getNativeLanguages() : \Drupal::languageManager()->getLanguages();
    return isset($languages[$value]) ? $languages[$value]->getName() : '';
  }

}
