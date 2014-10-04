<?php

/**
 * @file
 * Contains \Drupal\language\Element\LanguageConfiguration.
 */

namespace Drupal\language\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides language element configuration.
 *
 * @todo Annotate once https://www.drupal.org/node/2326409 is in.
 *   FormElement("language_configuration")
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
    $element['langcode'] = array(
      '#type' => 'select',
      '#title' => t('Default language'),
      '#options' => $options + static::getDefaultOptions(),
      '#description' => t('Explanation of the language options is found on the <a href="@languages_list_page">languages list page</a>.', array('@languages_list_page' => \Drupal::url('language.admin_overview'))),
      '#default_value' => isset($element['#default_value']['langcode']) ? $element['#default_value']['langcode'] : NULL,
    );

    $element['language_show'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show language selector on create and edit pages'),
      '#default_value' => isset($element['#default_value']['language_show']) ? $element['#default_value']['language_show'] : NULL,
    );

    // Add the entity type and bundle information to the form if they are set.
    // They will be used, in the submit handler, to generate the names of the
    // variables that will store the settings and are a way to uniquely identify
    // the entity.
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
      if (isset($form['actions']['submit']['#submit']) && array_search('language_configuration_element_submit', $form['actions']['submit']['#submit']) === FALSE) {
        $form['actions']['submit']['#submit'][] = 'language_configuration_element_submit';
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
      LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language (!language)", array('!language' => static::languageManager()->getDefaultLanguage()->name)),
      'current_interface' => t('Current interface language'),
      'authors_default' => t("Author's preferred language"),
    );

    $languages = static::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $language_options[$langcode] = $language->isLocked() ? t('- @name -', array('@name' => $language->name)) : $language->name;
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
