<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\Condition\Language.
 */

namespace Drupal\language\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a 'Language' condition.
 *
 * @Condition(
 *   id = "language",
 *   label = @Translation("Language"),
 *   context = {
 *     "language" = @ContextDefinition("language", label = @Translation("Language"))
 *   }
 * )
 *
 */
class Language extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (\Drupal::languageManager()->isMultilingual()) {
      // Fetch languages.
      $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_CONFIGURABLE);
      $langcodes_options = array();
      foreach ($languages as $language) {
        $langcodes_options[$language->getId()] = $language->getName();
      }
      $form['langcodes'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Language selection'),
        '#default_value' => $this->configuration['langcodes'],
        '#options' => $langcodes_options,
        '#description' => $this->t('Select languages to enforce. If none are selected, all languages will be allowed.'),
      );
    }
    else {
      $form['langcodes'] = array(
        '#type' => 'value',
        '#default_value' => $this->configuration['langcodes'],
      );
    }
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['langcodes'] = array_filter($form_state->getValue('langcodes'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $language_list = language_list(LanguageInterface::STATE_ALL);
    $selected = $this->configuration['langcodes'];
    // Reduce the language list to an array of language names.
    $language_names = array_reduce($language_list, function(&$result, $item) use ($selected) {
      // If the current item of the $language_list array is one of the selected
      // languages, add it to the $results array.
      if (!empty($selected[$item->getId()])) {
        $result[$item->getId()] = $item->getName();
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
    if (empty($this->configuration['langcodes']) && !$this->isNegated()) {
      return TRUE;
    }

    $language = $this->getContextValue('language');
    // Language visibility settings.
    return !empty($this->configuration['langcodes'][$language->getId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('langcodes' => array()) + parent::defaultConfiguration();
  }

}
