<?php

/**
 * @file
 * The content translation administration forms.
 */

use Drupal\content_translation\BundleTranslationSettingsInterface;
use Drupal\content_translation\ContentTranslationManager;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;

/**
 * Returns a form element to configure field synchronization.
 *
 * @param \Drupal\Core\Field\FieldDefinitionInterface $field
 *   A field definition object.
 * @param string $element_name
 *   (optional) The element name, which is added to drupalSettings so that
 *   javascript can manipulate the form element.
 *
 * @return array
 *   A form element to configure field synchronization.
 */
function content_translation_field_sync_widget(FieldDefinitionInterface $field, $element_name = 'third_party_settings[content_translation][translation_sync]') {
  // No way to store field sync information on this field.
  if (!($field instanceof ThirdPartySettingsInterface)) {
    return [];
  }

  $element = [];
  $definition = \Drupal::service('plugin.manager.field.field_type')->getDefinition($field->getType());
  $column_groups = $definition['column_groups'];
  if (!empty($column_groups) && count($column_groups) > 1) {
    $options = [];
    $default = [];
    $require_all_groups_for_translation = [];

    foreach ($column_groups as $group => $info) {
      $options[$group] = $info['label'];
      $default[$group] = !empty($info['translatable']) ? $group : FALSE;
      if (!empty($info['require_all_groups_for_translation'])) {
        $require_all_groups_for_translation[] = $group;
      }
    }

    $default = $field->getThirdPartySetting('content_translation', 'translation_sync', $default);

    $element = [
      '#type' => 'checkboxes',
      '#title' => t('Translatable elements'),
      '#options' => $options,
      '#default_value' => $default,
    ];

    if ($require_all_groups_for_translation) {
      // The actual checkboxes are sometimes rendered separately and the parent
      // element is ignored. Attach to the first option to ensure that this
      // does not get lost.
      $element[key($options)]['#attached']['drupalSettings']['contentTranslationDependentOptions'] = [
        'dependent_selectors' => [
          $element_name => $require_all_groups_for_translation,
        ],
      ];
      $element[key($options)]['#attached']['library'][] = 'content_translation/drupal.content_translation.admin';
    }
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

  /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
  $content_translation_manager = \Drupal::service('content_translation.manager');
  $default = $form['entity_types']['#default_value'];
  foreach ($default as $entity_type_id => $enabled) {
    $default[$entity_type_id] = $enabled || $content_translation_manager->isEnabled($entity_type_id) ? $entity_type_id : FALSE;
  }
  $form['entity_types']['#default_value'] = $default;

  $form['#attached']['library'][] = 'content_translation/drupal.content_translation.admin';

  $entity_manager = Drupal::entityManager();
  $bundle_info_service = \Drupal::service('entity_type.bundle.info');
  foreach ($form['#labels'] as $entity_type_id => $label) {
    $entity_type = $entity_manager->getDefinition($entity_type_id);
    $storage_definitions = $entity_type instanceof ContentEntityTypeInterface ? $entity_manager->getFieldStorageDefinitions($entity_type_id) : [];

    $entity_type_translatable = $content_translation_manager->isSupported($entity_type_id);
    foreach ($bundle_info_service->getBundleInfo($entity_type_id) as $bundle => $bundle_info) {
      // Here we do not want the widget to be altered and hold also the "Enable
      // translation" checkbox, which would be redundant. Hence we add this key
      // to be able to skip alterations. Alter the title and display the message
      // about UI integration.
      $form['settings'][$entity_type_id][$bundle]['settings']['language']['#content_translation_skip_alter'] = TRUE;
      if (!$entity_type_translatable) {
        $form['settings'][$entity_type_id]['#title'] = t('@label (Translation is not supported).', ['@label' => $entity_type->getLabel()]);
        continue;
      }

      // Displayed the "shared fields widgets" toggle.
      if ($content_translation_manager instanceof BundleTranslationSettingsInterface) {
        $settings = $content_translation_manager->getBundleTranslationSettings($entity_type_id, $bundle);
        $force_hidden = ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id, $bundle);
        $form['settings'][$entity_type_id][$bundle]['settings']['content_translation']['untranslatable_fields_hide'] = [
          '#type' => 'checkbox',
          '#title' => t('Hide non translatable fields on translation forms'),
          '#default_value' => $force_hidden || !empty($settings['untranslatable_fields_hide']),
          '#disabled' => $force_hidden,
          '#description' => $force_hidden ? t('Moderated content requires non-translatable fields to be edited in the original language form.') : '',
          '#states' => [
            'visible' => [
              ':input[name="settings[' . $entity_type_id . '][' . $bundle . '][translatable]"]' => [
                'checked' => TRUE,
              ],
            ],
          ],
        ];
      }

      $fields = $entity_manager->getFieldDefinitions($entity_type_id, $bundle);
      if ($fields) {
        foreach ($fields as $field_name => $definition) {
          if ($definition->isComputed() || (!empty($storage_definitions[$field_name]) && _content_translation_is_field_translatability_configurable($entity_type, $storage_definitions[$field_name]))) {
            $form['settings'][$entity_type_id][$bundle]['fields'][$field_name] = [
              '#label' => $definition->getLabel(),
              '#type' => 'checkbox',
              '#default_value' => $definition->isTranslatable(),
            ];
            // Display the column translatability configuration widget.
            $column_element = content_translation_field_sync_widget($definition, "settings[{$entity_type_id}][{$bundle}][columns][{$field_name}]");
            if ($column_element) {
              $form['settings'][$entity_type_id][$bundle]['columns'][$field_name] = $column_element;
            }
          }
        }
        if (!empty($form['settings'][$entity_type_id][$bundle]['fields'])) {
          // Only show the checkbox to enable translation if the bundles in the
          // entity might have fields and if there are fields to translate.
          $form['settings'][$entity_type_id][$bundle]['translatable'] = [
            '#type' => 'checkbox',
            '#default_value' => $content_translation_manager->isEnabled($entity_type_id, $bundle),
          ];
        }
      }
    }
  }

  $form['#validate'][] = 'content_translation_form_language_content_settings_validate';
  $form['#submit'][] = 'content_translation_form_language_content_settings_submit';
}

/**
 * Checks whether translatability should be configurable for a field.
 *
 * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
 *   The entity type definition.
 * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
 *   The field storage definition.
 *
 * @return bool
 *   TRUE if field translatability can be configured, FALSE otherwise.
 *
 * @internal
 */
function _content_translation_is_field_translatability_configurable(EntityTypeInterface $entity_type, FieldStorageDefinitionInterface $definition) {
  // Allow to configure only fields supporting multilingual storage. We skip our
  // own fields as they are always translatable. Additionally we skip a set of
  // well-known fields implementing entity system business logic.
  return $definition->isTranslatable() &&
    $definition->getProvider() != 'content_translation' &&
    !in_array($definition->getName(), [$entity_type->getKey('langcode'), $entity_type->getKey('default_langcode'), 'revision_translation_affected']);
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

  array_unshift($build['#header'], ['data' => t('Translatable'), 'class' => ['translatable']]);
  $rows = [];

  foreach (Element::children($element) as $bundle) {
    $field_names = !empty($element[$bundle]['fields']) ? Element::children($element[$bundle]['fields']) : [];
    if (!empty($element[$bundle]['translatable'])) {
      $checkbox_id = $element[$bundle]['translatable']['#id'];
    }
    $rows[$bundle] = $build['#rows'][$bundle];

    if (!empty($element[$bundle]['translatable'])) {
      $translatable = [
        'data' => $element[$bundle]['translatable'],
        'class' => ['translatable'],
      ];
      array_unshift($rows[$bundle]['data'], $translatable);

      $rows[$bundle]['data'][1]['data']['#prefix'] = '<label for="' . $checkbox_id . '">';
    }
    else {
      $translatable = [
        'data' => t('N/A'),
        'class' => ['untranslatable'],
      ];
      array_unshift($rows[$bundle]['data'], $translatable);
    }

    foreach ($field_names as $field_name) {
      $field_element = &$element[$bundle]['fields'][$field_name];
      $rows[] = [
        'data' => [
          [
            'data' => \Drupal::service('renderer')->render($field_element),
            'class' => ['translatable'],
          ],
          [
            'data' => [
              '#prefix' => '<label for="' . $field_element['#id'] . '">',
              '#suffix' => '</label>',
              'bundle' => [
                '#prefix' => '<span class="visually-hidden">',
                '#suffix' => '</span> ',
                '#plain_text' => $element[$bundle]['settings']['#label'],
              ],
              'field' => [
                '#plain_text' => $field_element['#label'],
              ],
            ],
            'class' => ['field'],
          ],
          [
            'data' => '',
            'class' => ['operations'],
          ],
        ],
        'class' => ['field-settings'],
      ];

      if (!empty($element[$bundle]['columns'][$field_name])) {
        $column_element = &$element[$bundle]['columns'][$field_name];
        foreach (Element::children($column_element) as $key) {
          $column_label = $column_element[$key]['#title'];
          unset($column_element[$key]['#title']);
          $rows[] = [
            'data' => [
              [
                'data' => \Drupal::service('renderer')->render($column_element[$key]),
                'class' => ['translatable'],
              ],
              [
                'data' => [
                  '#prefix' => '<label for="' . $column_element[$key]['#id'] . '">',
                  '#suffix' => '</label>',
                  'bundle' => [
                    '#prefix' => '<span class="visually-hidden">',
                    '#suffix' => '</span> ',
                    '#plain_text' => $element[$bundle]['settings']['#label'],
                  ],
                  'field' => [
                    '#prefix' => '<span class="visually-hidden">',
                    '#suffix' => '</span> ',
                    '#plain_text' => $field_element['#label'],
                  ],
                  'columns' => [
                    '#plain_text' => $column_label,
                  ],
                ],
                'class' => ['column'],
              ],
              [
                'data' => '',
                'class' => ['operations'],
              ],
            ],
            'class' => ['column-settings'],
          ];
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
          $t_args = ['%bundle' => $form['settings'][$entity_type][$bundle]['settings']['#label']];
          $form_state->setErrorByName($name, t('At least one field needs to be translatable to enable %bundle for translation.', $t_args));
        }

        $values = $bundle_settings['settings']['language'];
        if (empty($values['language_alterable']) && \Drupal::languageManager()->isLanguageLocked($values['langcode'])) {
          foreach (\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_LOCKED) as $language) {
            $locked_languages[] = $language->getName();
          }
          $form_state->setErrorByName($name, t('Translation is not supported if language is always one of: @locked_languages', ['@locked_languages' => implode(', ', $locked_languages)]));
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
  /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
  $content_translation_manager = \Drupal::service('content_translation.manager');
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
        $content_translation_manager->setEnabled($entity_type_id, $bundle, $bundle_settings['translatable']);

        // Store any other bundle settings.
        if ($content_translation_manager instanceof BundleTranslationSettingsInterface) {
          $content_translation_manager->setBundleTranslationSettings($entity_type_id, $bundle, $bundle_settings['settings']['content_translation']);
        }

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
  \Drupal::entityTypeManager()->clearCachedDefinitions();
  \Drupal::service('router.builder')->setRebuildNeeded();
}
