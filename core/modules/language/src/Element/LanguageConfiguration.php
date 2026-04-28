<?php

namespace Drupal\language\Element;

use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Defines an element for language configuration for a single field.
 */
#[FormElement('language_configuration')]
class LanguageConfiguration extends FormElementBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [
        [static::class, 'processLanguageConfiguration'],
      ],
    ];
  }

  /**
   * Process handler for the language_configuration form element.
   */
  public static function processLanguageConfiguration(&$element, FormStateInterface $form_state, &$form) {
    $options = $element['#options'] ?? [];
    // Avoid validation failure since we are moving the '#options' key in the
    // nested 'language' select element.
    unset($element['#options']);
    /** @var \Drupal\language\Entity\ContentLanguageSettings $default_config */
    $default_config = $element['#default_value'];
    $element['langcode'] = [
      '#type' => 'select',
      '#title' => t('Default language'),
      '#options' => $options + static::getDefaultOptions(),
      '#description' => t('Explanation of the language options is found on the <a href=":languages_list_page">languages list page</a>.', [':languages_list_page' => Url::fromRoute('entity.configurable_language.collection')->toString()]),
      '#default_value' => ($default_config != NULL) ? $default_config->getDefaultLangcode() : LanguageInterface::LANGCODE_SITE_DEFAULT,
    ];

    $element['language_alterable'] = [
      '#type' => 'checkbox',
      '#title' => t('Show language selector on create and edit pages'),
      '#default_value' => ($default_config != NULL) ? $default_config->isLanguageAlterable() : FALSE,
    ];

    // Add the entity type and bundle information to the form if they are set.
    // They will be used, in the submit handler, to generate the names of the
    // configuration entities that will store the settings and are a way to
    // uniquely identify the entity.
    $language = $form_state->get('language') ?: [];
    $language += [
      $element['#name'] => [
        'entity_type' => $element['#entity_information']['entity_type'],
        'bundle' => $element['#entity_information']['bundle'],
      ],
    ];
    $form_state->set('language', $language);

    // Do not add the submit callback for the language content settings page,
    // which is handled separately.
    if ($form['#form_id'] != 'language_content_settings_form') {
      // Determine where to attach the language_configuration element submit
      // handler.
      // @todo Form API: Allow form widgets/sections to declare #submit
      //   handlers.
      $submit_name = isset($form['actions']['save_continue']) ? 'save_continue' : 'submit';
      $callback = static::class . '::submit';
      if (isset($form['actions'][$submit_name]['#submit']) && array_search($callback, $form['actions'][$submit_name]['#submit']) === FALSE) {
        $form['actions'][$submit_name]['#submit'][] = $callback;
      }
      elseif (array_search($callback, $form['#submit']) === FALSE) {
        $form['#submit'][] = $callback;
      }
    }
    return $element;
  }

  /**
   * Submit handler for the forms that have a language_configuration element.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state instance.
   */
  public static function submit(array &$form, FormStateInterface $form_state): void {
    // Iterate through all the language_configuration elements and save their
    // values. In case we are editing a bundle, we must check the new bundle
    // name, because, e.g., hook_ENTITY_update has been fired before.
    if ($language = $form_state->get('language')) {
      foreach ($language as $element_name => $values) {
        $entity_type_id = $values['entity_type'];
        $bundle = $values['bundle'];
        $form_object = $form_state->getFormObject();
        if ($form_object instanceof EntityFormInterface) {
          $entity = $form_object->getEntity();
          if ($entity->getEntityType()->getBundleOf()) {
            $bundle = $entity->id();
            $language[$element_name]['bundle'] = $bundle;
          }
        }
        $config = ContentLanguageSettings::loadByEntityTypeBundle($entity_type_id, $bundle);
        $config->setDefaultLangcode($form_state->getValue([$element_name, 'langcode']));
        $config->setLanguageAlterable($form_state->getValue([$element_name, 'language_alterable']));
        $config->save();

        // Set the form_state language with the updated bundle.
        $form_state->set('language', $language);
      }
    }
  }

  /**
   * Returns the default options for the language configuration form element.
   *
   * @return array
   *   An array containing the default options.
   */
  protected static function getDefaultOptions() {
    $language_options = [
      LanguageInterface::LANGCODE_SITE_DEFAULT => t("Site's default language (@language)", ['@language' => static::languageManager()->getDefaultLanguage()->getName()]),
      'current_interface' => t('Interface text language selected for page'),
      'authors_default' => t("Author's preferred language"),
    ];

    $languages = static::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $language_options[$langcode] = $language->isLocked() ? t('- @name -', ['@name' => $language->getName()]) : $language->getName();
    }

    return $language_options;
  }

  /**
   * Wraps the language manager.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager service.
   */
  protected static function languageManager() {
    return \Drupal::languageManager();
  }

}
