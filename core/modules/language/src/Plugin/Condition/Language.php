<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Condition\Language.
 */

namespace Drupal\language\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Language\Language as Lang;

/**
 * Provides a 'Language' condition.
 *
 * @Condition(
 *   id = "language",
 *   label = @Translation("Language"),
 *   context = {
 *     "language" = {
 *       "type" = "language"
 *     }
 *   }
 * )
 */
class Language extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    if (\Drupal::languageManager()->isMultilingual()) {
      // Fetch languages.
      $languages = language_list(Lang::STATE_ALL);
      $langcodes_options = array();
      foreach ($languages as $language) {
        $langcodes_options[$language->id] = $language->label();
      }
      $form['langcodes'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Language selection'),
        '#default_value' => !empty($this->configuration['langcodes']) ? $this->configuration['langcodes'] : array(),
        '#options' => $langcodes_options,
        '#description' => t('Select languages to enforce. If none are selected, all languages will be allowed.'),
      );
    }
    else {
      $form['language']['langcodes'] = array(
        '#type' => 'value',
        '#value' => !empty($this->configuration['langcodes']) ? $this->configuration['langcodes'] : array()
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['langcodes'] = array_filter($form_state['values']['langcodes']);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $language_list = language_list(Lang::STATE_ALL);
    $selected = $this->configuration['langcodes'];
    // Reduce the language list to an array of language names.
    $language_names = array_reduce($language_list, function(&$result, $item) use ($selected) {
      // If the current item of the $language_list array is one of the selected
      // languages, add it to the $results array.
      if (!empty($selected[$item->id])) {
        $result[$item->id] = $item->name;
      }
      return $result;
    }, array());

    // If we have more than one language selected, separate them by commas.
    if (count($this->configuration['langcodes']) > 1) {
      $languages = implode(', ', $language_names);
    }
    else {
      // If we have just one language just grab the only present value.
      $languages = array_pop($language_names);
    }
    if (!empty($this->configuration['negate'])) {
      return t('The language is not @languages.', array('@languages' => $languages));
    }
    return t('The language is @languages.', array('@languages' => $languages));
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $language = $this->getContextValue('language');
    // Language visibility settings.
    if (!empty($this->configuration['langcodes'])) {
      return !empty($this->configuration['langcodes'][$language->id]);
    }
    return TRUE;
  }

}
