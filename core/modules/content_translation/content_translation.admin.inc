<?php

/**
 * @file
 * The content translation administration forms.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;

/**
 * Returns a form element to configure field synchronization.
 *
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field
 *   A field definition object.
 *
 * @return array
 *   A form element to configure field synchronization.
 */
function content_translation_field_sync_widget(FieldDefinitionInterface $field) {
  // No way to store field sync information on this field.
  if (!($field instanceof ThirdPartySettingsInterface)) {
    return array();
  }

  $element = array();
  $definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field->getType());
  $column_groups = $definition['column_groups'];
  if (!empty($column_groups) && count($column_groups) > 1) {
    $options = array();
    $default = array();

    foreach ($column_groups as $group => $info) {
      $options[$group] = $info['label'];
      $default[$group] = !empty($info['translatable']) ? $group : FALSE;
    }

    $settings = array('dependent_selectors' => array('instance[third_party_settings][content_translation][translation_sync]' => array('file')));
    $default = $field->getThirdPartySetting('content_translation', 'translation_sync', $default);

    $element = array(
      '#type' => 'checkboxes',
      '#title' => t('Translatable elements'),
      '#options' => $options,
      '#default_value' => $default,
      '#attached' => array(
        'library' => array(
          'content_translation/drupal.content_translation.admin',
        ),
        'drupalSettings' => [
          'contentTranslationDependentOptions' => $settings,
        ],
      ),
    );
  }

  return $element;
}

/**
 * (proxied) Implements hook_form_FORM_ID_alter().
 */
function _content_translation_form_language_content_settings_form_alter(array &$form, FormStateInterface $form_state) {
  // Inject into the content language settings the translation settings if the
  // user has the required permission.
  if (!\Drupal::currentUser()->hasPermission('administer content translation')) {
    return;
  }

  $content_translation_manager = \Drupal::service('content_translation.manager');
  $default = $form['entity_types']['#default_value'];
  foreach ($default as $entity_type_id => $enabled) {
    $default[$entity_type_id] = $enabled || $content_translation_manager->isEnabled($entity_type_id) ? $entity_type_id : FALSE;
  }
  $form['entity_types']['#default_value'] = $default;

  $form['#attached']['library'][] = 'content_translation/drupal.content_translation.admin';

  $dependent_options_settings = array();
  $entity_manager = Drupal::entityManager();
  foreach ($form['#labels'] as $entity_type_id => $label) {
    $entity_type = $entity_manager->getDefinition($entity_type_id);
    $storage_definitions = $entity_type instanceof ContentEntityTypeInterface ? $entity_manager->getFieldStorageDefinitions($entity_type_id) : array();

    foreach (entity_get_bundles($entity_type_id) as $bundle => $bundle_info) {
      // Here we do not want the widget to be altered and hold also the "Enable
      // translation" checkbox, which would be redundant. Hence we add this key
      // to be able to skip alterations.
      $form['settings'][$entity_type_id][$bundle]['settings']['language']['#content_translation_skip_alter'] = TRUE;

      $fields = $entity_manager->getFieldDefinitions($entity_type_id, $bundle);
      if ($fields) {
        foreach ($fields as $field_name => $definition) {
          // Allow to configure only fields supporting multilingual storage.
          if (!empty($storage_definitions[$field_name]) && $storage_definitions[$field_name]->isTranslatable()) {
            $form['settings'][$entity_type_id][$bundle]['fields'][$field_name] = array(
              '#label' => $definition->getLabel(),
              '#type' => 'checkbox',
              '#default_value' => $definition->isTranslatable(),
            );
            // Display the column translatability configuration widget.
            $column_element = content_translation_field_sync_widget($definition);
            if ($column_element) {
              $form['settings'][$entity_type_id][$bundle]['columns'][$field_name] = $column_element;
              // @todo This should not concern only files.
              if (isset($column_element['#options']['file'])) {
                $dependent_options_settings["settings[{$entity_type_id}][{$bundle}][columns][{$field_name}]"] = array('file');
              }
            }
          }
        }
        if (!empty($form['settings'][$entity_type_id][$bundle]['fields'])) {
          // Only show the checkbox to enable translation if the bundles in the
          // entity might have fields and if there are fields to translate.
          $form['settings'][$entity_type_id][$bundle]['translatable'] = array(
            '#type' => 'checkbox',
            '#default_value' => $content_translation_manager->isEnabled($entity_type_id, $bundle),
          );
        }
      }
    }
  }

  $settings = array('dependent_selectors' => $dependent_options_settings);
  $form['#attached']['drupalSettings']['contentTranslationDependentOptions'] = $settings;
  $form['#validate'][] = 'content_translation_form_language_content_settings_validate';
  $form['#submit'][] = 'content_translation_form_language_content_settings_submit';
}

/**
 * (proxied) Implements hook_preprocess_HOOK();
 */
function _content_translation_preprocess_language_content_settings_table(&$variables) {
  // Alter the 'build' variable injecting the translation settings if the user
  // has the required permission.
  if (!\Drupal::currentUser()->hasPermission('administer content translation')) {
    return;
  }

  $element = $variables['element'];
  $build = &$variables['build'];

  array_unshift($build['#header'], array('data' => t('Translatable'), 'class' => array('translatable')));
  $rows = array();

  foreach (Element::children($element) as $bundle) {
    $field_names = !empty($element[$bundle]['fields']) ? Element::children($element[$bundle]['fields']) : array();
    if (!empty($element[$bundle]['translatable'])) {
      $checkbox_id = $element[$bundle]['translatable']['#id'];
    }
    $rows[$bundle] = $build['#rows'][$bundle];

    if (!empty($element[$bundle]['translatable'])) {
      $translatable = array(
        'data' => $element[$bundle]['translatable'],
        'class' => array('translatable'),
      );
      array_unshift($rows[$bundle]['data'], $translatable);

      $rows[$bundle]['data'][1]['data']['#prefix'] = '<label for="' . $checkbox_id . '">';
    }
    else {
      $translatable = array(
        'class' => array('untranslatable'),
      );
      array_unshift($rows[$bundle]['data'], $translatable);
    }

    foreach ($field_names as $field_name) {
      $field_element = &$element[$bundle]['fields'][$field_name];
      $rows[] = array(
        'data' => array(
          array(
            'data' => drupal_render($field_element),
            'class' => array('translatable'),
          ),
          array(
            'data' => array(
              '#prefix' => '<label for="' . $field_element['#id'] . '">',
              '#suffix' => '</label>',
              'bundle' => array(
                '#prefix' => '<span class="visually-hidden">',
                '#suffix' => '</span> ',
                '#markup' => String::checkPlain($element[$bundle]['settings']['#label']),
              ),
              'field' => array(
                '#markup' => String::checkPlain($field_element['#label']),
              ),
            ),
            'class' => array('field'),
          ),
          array(
            'data' => '',
            'class' => array('operations'),
          ),
        ),
        'class' => array('field-settings'),
      );

      if (!empty($element[$bundle]['columns'][$field_name])) {
        $column_element = &$element[$bundle]['columns'][$field_name];
        foreach (Element::children($column_element) as $key) {
          $column_label = $column_element[$key]['#title'];
          unset($column_element[$key]['#title']);
          $rows[] = array(
            'data' => array(
              array(
                'data' => drupal_render($column_element[$key]),
                'class' => array('translatable'),
              ),
              array(
                'data' => array(
                  '#prefix' => '<label for="' . $column_element[$key]['#id'] . '">',
                  '#suffix' => '</label>',
                  'bundle' => array(
                    '#prefix' => '<span class="visually-hidden">',
                    '#suffix' => '</span> ',
                    '#markup' => String::checkPlain($element[$bundle]['settings']['#label']),
                  ),
                  'field' => array(
                    '#prefix' => '<span class="visually-hidden">',
                    '#suffix' => '</span> ',
                    '#markup' => String::checkPlain($field_element['#label']),
                  ),
                  'columns' => array(
                    '#markup' => String::checkPlain($column_label),
                  ),
                ),
                'class' => array('column'),
              ),
              array(
                'data' => '',
                'class' => array('operations'),
              ),
            ),
            'class' => array('column-settings'),
          );
        }
      }
    }
  }

  $build['#rows'] = $rows;
}

/**
 * Form validation handler for content_translation_admin_settings_form().
 *
 * @see content_translation_admin_settings_form_submit()
 */
function content_translation_form_language_content_settings_validate(array $form, FormStateInterface $form_state) {
  $settings = &$form_state->getValue('settings');
  foreach ($settings as $entity_type => $entity_settings) {
    foreach ($entity_settings as $bundle => $bundle_settings) {
      if (!empty($bundle_settings['translatable'])) {
        $name = "settings][$entity_type][$bundle][translatable";

        $translatable_fields = isset($settings[$entity_type][$bundle]['fields']) ? array_filter($settings[$entity_type][$bundle]['fields']) : FALSE;
        if (empty($translatable_fields)) {
          $t_args = array('%bundle' => $form['settings'][$entity_type][$bundle]['settings']['#label']);
          $form_state->setErrorByName($name, t('At least one field needs to be translatable to enable %bundle for translation.', $t_args));
        }

        $values = $bundle_settings['settings']['language'];
        if (empty($values['language_alterable']) && \Drupal::languageManager()->isLanguageLocked($values['langcode'])) {
          foreach (\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_LOCKED) as $language) {
            $locked_languages[] = $language->getName();
          }
          $form_state->setErrorByName($name, t('Translation is not supported if language is always one of: @locked_languages', array('@locked_languages' => implode(', ', $locked_languages))));
        }
      }
    }
  }
}

/**
 * Form submission handler for content_translation_admin_settings_form().
 *
 * @see content_translation_admin_settings_form_validate()
 */
function content_translation_form_language_content_settings_submit(array $form, FormStateInterface $form_state) {
  $entity_types = $form_state->getValue('entity_types');
  $settings = &$form_state->getValue('settings');

  // If an entity type is not translatable all its bundles and fields must be
  // marked as non-translatable. Similarly, if a bundle is made non-translatable
  // all of its fields will be not translatable.
  foreach ($settings as $entity_type_id => &$entity_settings) {
    foreach ($entity_settings as $bundle => &$bundle_settings) {
      $fields = \Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle);
      if (!empty($bundle_settings['translatable'])) {
        $bundle_settings['translatable'] = $bundle_settings['translatable'] && $entity_types[$entity_type_id];
      }
      if (!empty($bundle_settings['fields'])) {
        foreach ($bundle_settings['fields'] as $field_name => $translatable) {
          $translatable = $translatable && $bundle_settings['translatable'];
          // If we have column settings and no column is translatable, no point
          // in making the field translatable.
          if (isset($bundle_settings['columns'][$field_name]) && !array_filter($bundle_settings['columns'][$field_name])) {
            $translatable = FALSE;
          }
          $field_config = $fields[$field_name]->getConfig($bundle);
          if ($field_config->isTranslatable() != $translatable) {
            $field_config
              ->setTranslatable($translatable)
              ->save();
          }
        }
      }
      if (isset($bundle_settings['translatable'])) {
        // Store whether a bundle has translation enabled or not.
        \Drupal::service('content_translation.manager')->setEnabled($entity_type_id, $bundle, $bundle_settings['translatable']);

        // Save translation_sync settings.
        if (!empty($bundle_settings['columns'])) {
          foreach ($bundle_settings['columns'] as $field_name => $column_settings) {
            $field_config = $fields[$field_name]->getConfig($bundle);
            if ($field_config->isTranslatable()) {
              $field_config->setThirdPartySetting('content_translation', 'translation_sync', $column_settings);
            }
            // If the field does not have translatable enabled we need to reset
            // the sync settings to their defaults.
            else {
              $field_config->unsetThirdPartySetting('content_translation', 'translation_sync');
            }
            $field_config->save();
          }
        }
      }
    }
  }

  // Ensure entity and menu router information are correctly rebuilt.
  \Drupal::entityManager()->clearCachedDefinitions();
  \Drupal::service('router.builder_indicator')->setRebuildNeeded();
}
