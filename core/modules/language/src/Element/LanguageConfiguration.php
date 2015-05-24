<?php

/**
 * @file
 * Contains \Drupal\language\Element\LanguageConfiguration.
 */

namespace Drupal\language\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Provides language element configuration.
 *
 * @FormElement("language_configuration")
 */
class LanguageConfiguration extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => array(
        array($class, 'processLanguageConfiguration'),
      ),
    );
  }

  /**
   * Process handler for the language_configuration form element.
   */
  public static function processLanguageConfiguration(&$element, FormStateInterface $form_state, &$form) {
    $options = isset($element['#options']) ? $element['#options'] : array();
    // Avoid validation failure since we are moving the '#options' key in the
    // nested 'language' select element.
    unset($element['#options']);
    /** @var ContentLanguageSettings $default_config */
    $default_config = $element['#default_value'];
    $element['langcode'] = array(
      '#type' => 'select',
      '#title' => t('Default language'),
      '#options' => $options + static::getDefaultOptions(),
      '#description' => t('Explanation of the language options is found on the <a href="@languages_list_page">languages list page</a>.', array('@languages_list_page' => \Drupal::url('entity.configurable_language.collection'))),
      '#default_value' => ($default_config != NULL) ? $default_config->getDefaultLangcode() : LanguageInterface::LANGCODE_SITE_DEFAULT,
    );

    $element['language_alterable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show language selector on create and edit pages'),
      '#default_value' => ($default_config != NULL) ? $default_config->isLanguageAlterable() : FALSE,
    );

    // Add the entity type and bundle information to the form if they are set.
    // They will be used, in the submit handler, to generate the names of the
    // configuration entities that will store the settings and are a way to uniquely
    // identify the entity.
    $language = $form_state->get('language') ?: [];
    $language += array(
      $element['#name'] => array(
        'entity_type' => $element['#entity_information']['entity_type'],
        'bundle' => $element['#entity_information']['bundle'],
      ),
    );
    $form_state->set('language', $language);

    // Do not add the submit callback for the language content settings page,
    // which is handled separately.
    if ($form['#form_id'] != 'language_content_settings_form') {
      // Determine where to attach the language_configuration element submit
      // handler.
      // @todo Form API: Allow form widgets/sections to declare #submit
      //   handlers.
      $submit_name = isset($form['actions']['save_continue']) ? 'save_continue' : 'submit';
      if (isset($form['actions'][$submit_name]['#submit']) && array_search('language_configuration_element_submit', $form['actions'][$submit_name]['#submit']) === FALSE) {
        $form['actions'][$submit_name]['#submit'][] = 'language_configuration_element_submit';
      }
      elseif (array_search('language_configuration_element_submit', $form['#submit']) === FALSE) {
        $form['#submit'][] = 'language_configuration_element_submit';
      }
    }
    return $element;
  }

  /**
   * Returns the default options for the language configuration form element.
   *
   * @return array
   *   An array containing the default options.
   */
  protected static function getDefaultOptions() {
    $language_options = array(
      LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language (!language)", array('!language' => static::languageManager()->getDefaultLanguage()->getName())),
      'current_interface' => t('Interface text language selected for page'),
      'authors_default' => t("Author's preferred language"),
    );

    $languages = static::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $language_options[$langcode] = $language->isLocked() ? t('- @name -', array('@name' => $language->getName())) : $language->getName();
    }

    return $language_options;
  }

  /**
   * Wraps the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   */
  protected static function languageManager() {
    return \Drupal::languageManager();
  }

}
