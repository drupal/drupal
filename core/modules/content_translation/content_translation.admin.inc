<?php

/**
 * @file
 * The content translation administration forms.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\field\Entity\Field;
use Drupal\field\Entity\FieldInstance;

/**
 * Returns a form element to configure field synchronization.
 *
 * @param \Drupal\field\Entity\Field $field
 *   A field definition array.
 * @param \Drupal\field\Entity\FieldInstance $instance
 *   A field instance definition object.
 *
 * @return array
 *   A form element to configure field synchronization.
 */
function content_translation_field_sync_widget(Field $field, FieldInstance $instance) {
  $element = array();

  if (!empty($field['settings']['column_groups']) && count($field['settings']['column_groups']) > 1) {
    $options = array();
    $default = array();

    foreach ($field['settings']['column_groups'] as $group => $info) {
      $options[$group] = $info['label'];
      $default[$group] = !empty($info['translatable']) ? $group : FALSE;
    }

    $settings = array('dependent_selectors' => array('instance[settings][translation_sync]' => array('file')));

    $element = array(
      '#type' => 'checkboxes',
      '#title' => t('Translatable elements'),
      '#options' => $options,
      '#default_value' => !empty($instance['settings']['translation_sync']) ? $instance['settings']['translation_sync'] : $default,
      '#attached' => array(
        'library' => array(
          array('content_translation', 'drupal.content_translation.admin'),
        ),
        'js' => array(
          array('data' => array('contentTranslationDependentOptions' => $settings), 'type' => 'setting'),
        ),
      ),
    );
  }

  return $element;
}

/**
 * (proxied) Implements hook_form_FORM_ID_alter().
 */
function _content_translation_form_language_content_settings_form_alter(array &$form, array &$form_state) {
  // Inject into the content language settings the translation settings if the
  // user has the required permission.
  if (!user_access('administer content translation')) {
    return;
  }

  $default = $form['entity_types']['#default_value'];
  foreach ($default as $entity_type => $enabled) {
    $default[$entity_type] = $enabled || content_translation_enabled($entity_type) ? $entity_type : FALSE;
  }
  $form['entity_types']['#default_value'] = $default;

  $form['#attached']['library'][] = array('content_translation', 'drupal.content_translation.admin');
  $form['#attached']['js'][] = array('data' => drupal_get_path('module', 'content_translation') . '/content_translation.admin.js', 'type' => 'file');

  $dependent_options_settings = array();
  foreach ($form['#labels'] as $entity_type => $label) {
    $entity_info = entity_get_info($entity_type);
    foreach (entity_get_bundles($entity_type) as $bundle => $bundle_info) {
      // Here we do not want the widget to be altered and hold also the "Enable
      // translation" checkbox, which would be redundant. Hence we add this key
      // to be able to skip alterations.
      $form['settings'][$entity_type][$bundle]['settings']['language']['#content_translation_skip_alter'] = TRUE;

      // Only show the checkbox to enable translation if the bundles in the
      // entity might have fields and if there are fields to translate.
      if (!empty($entity_info['fieldable'])) {
        $fields = field_info_instances($entity_type, $bundle);
        if ($fields) {
          $form['settings'][$entity_type][$bundle]['translatable'] = array(
            '#type' => 'checkbox',
            '#title' => $bundle,
            '#default_value' => content_translation_enabled($entity_type, $bundle),
          );

          // @todo Exploit field definitions once all core entities and field
          // types are migrated to the Entity Field API.
          foreach ($fields as $field_name => $instance) {
            $field = $instance->getField();
            $form['settings'][$entity_type][$bundle]['fields'][$field_name] = array(
              '#label' => $instance['label'],
              '#type' => 'checkbox',
              '#title' => $instance['label'],
              '#default_value' => $field['translatable'],
            );
            $column_element = content_translation_field_sync_widget($field, $instance);
            if ($column_element) {
              $form['settings'][$entity_type][$bundle]['columns'][$field_name] = $column_element;

              if (isset($column_element['#options']['file'])) {
                $dependent_options_settings["settings[{$entity_type}][{$bundle}][columns][{$field_name}]"] = array('file');
              }
            }
          }
        }
      }
    }
  }
  $settings = array('dependent_selectors' => $dependent_options_settings);
  $form['#attached']['js'][] = array('data' => array('contentTranslationDependentOptions' => $settings), 'type' => 'setting');
  $form['#validate'][] = 'content_translation_form_language_content_settings_validate';
  $form['#submit'][] = 'content_translation_form_language_content_settings_submit';
}

/**
 * (proxied) Implements hook_preprocess_HOOK();
 */
function _content_translation_preprocess_language_content_settings_table(&$variables) {
  // Alter the 'build' variable injecting the translation settings if the user
  // has the required permission.
  if (!user_access('administer content translation')) {
    return;
  }

  $element = $variables['element'];
  $build = &$variables['build'];

  array_unshift($build['#header'], array('data' => t('Translatable'), 'class' => array('translatable')));
  $rows = array();

  foreach (element_children($element) as $bundle) {
    $field_names = !empty($element[$bundle]['fields']) ? element_children($element[$bundle]['fields']) : array();
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
                '#markup' => check_plain($element[$bundle]['settings']['#label']),
              ),
              'field' => array(
                '#markup' => check_plain($field_element['#label']),
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
        foreach (element_children($column_element) as $key) {
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
                    '#markup' => check_plain($element[$bundle]['settings']['#label']),
                  ),
                  'field' => array(
                    '#prefix' => '<span class="visually-hidden">',
                    '#suffix' => '</span> ',
                    '#markup' => check_plain($field_element['#label']),
                  ),
                  'columns' => array(
                    '#markup' => check_plain($column_label),
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
function content_translation_form_language_content_settings_validate(array $form, array &$form_state) {
  $settings = &$form_state['values']['settings'];
  foreach ($settings as $entity_type => $entity_settings) {
    foreach ($entity_settings as $bundle => $bundle_settings) {
      if (!empty($bundle_settings['translatable'])) {
        $name = "settings][$entity_type][$bundle][translatable";

        $translatable_fields = isset($settings[$entity_type][$bundle]['fields']) ? array_filter($settings[$entity_type][$bundle]['fields']) : FALSE;
        if (empty($translatable_fields)) {
          $t_args = array('%bundle' => $form['settings'][$entity_type][$bundle]['settings']['#label']);
          form_set_error($name, t('At least one field needs to be translatable to enable %bundle for translation.', $t_args));
        }

        $values = $bundle_settings['settings']['language'];
        if (language_is_locked($values['langcode']) && empty($values['language_show'])) {
          foreach (language_list(Language::STATE_LOCKED) as $language) {
            $locked_languages[] = $language->name;
          }
          form_set_error($name, t('Translation is not supported if language is always one of: @locked_languages', array('@locked_languages' => implode(', ', $locked_languages))));
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
function content_translation_form_language_content_settings_submit(array $form, array &$form_state) {
  $entity_types = $form_state['values']['entity_types'];
  $settings = &$form_state['values']['settings'];

  // If an entity type is not translatable all its bundles and fields must be
  // marked as non-translatable. Similarly, if a bundle is made non-translatable
  // all of its fields will be not translatable.
  foreach ($settings as $entity_type => &$entity_settings) {
    foreach ($entity_settings as $bundle => &$bundle_settings) {
      if (!empty($bundle_settings['translatable'])) {
        $bundle_settings['translatable'] = $bundle_settings['translatable'] && $entity_types[$entity_type];
      }
      if (!empty($bundle_settings['fields'])) {
        foreach ($bundle_settings['fields'] as $field_name => $translatable) {
          $bundle_settings['fields'][$field_name] = $translatable && $bundle_settings['translatable'];
          // If we have column settings and no column is translatable, no point
          // in making the field translatable.
          if (isset($bundle_settings['columns'][$field_name]) && !array_filter($bundle_settings['columns'][$field_name])) {
            $bundle_settings['fields'][$field_name] = FALSE;
          }
        }
      }
    }
  }

  _content_translation_update_field_translatability($settings);
  drupal_set_message(t('Settings successfully updated.'));
}

/**
 * Stores content translation settings.
 *
 * @param array $settings
 *   An associative array of settings keyed by entity type and bundle. At bundle
 *   level the following keys are available:
 *   - translatable: The bundle translatability status, which is a bool.
 *   - settings: An array of language configuration settings as defined by
 *     language_save_default_configuration().
 *   - fields: An associative array with field names as keys and a boolean as
 *     value, indicating field translatability.
 *
 * @todo Remove this migration entirely once the Field API is converted to the
 *   Entity Field API.
 */
function _content_translation_update_field_translatability($settings) {
  $fields = array();
  foreach ($settings as $entity_type => $entity_settings) {
    foreach ($entity_settings as $bundle => $bundle_settings) {
      // Collapse field settings since here we have per instance settings, but
      // translatability has per-field scope. We assume that all the field
      // instances have the same value.
      if (!empty($bundle_settings['fields'])) {
        foreach ($bundle_settings['fields'] as $field_name => $translatable) {
          // If a field is enabled for translation for at least one instance we
          // need to mark it as translatable.
          $fields[$entity_type][$field_name] = $translatable || !empty($fields[$entity_type][$field_name]);
        }
      }
      // @todo Store non-configurable field settings to be able to alter their
      //   definition afterwards.
    }
  }

  $operations = array();
  foreach ($fields as $entity_type => $entity_type_fields) {
    foreach ($entity_type_fields as $field_name => $translatable) {
      $field = field_info_field($entity_type, $field_name);
      if ($field['translatable'] != $translatable) {
        // If a field is untranslatable, it can have no data except under
        // Language::LANGCODE_NOT_SPECIFIED. Thus we need a field to be translatable before
        // we convert data to the entity language. Conversely we need to switch
        // data back to Language::LANGCODE_NOT_SPECIFIED before making a field
        // untranslatable lest we lose information.
        $field_operations = array(
          array('content_translation_translatable_switch', array($translatable, $entity_type, $field_name)),
        );
        if ($field->hasData()) {
          $field_operations[] = array('content_translation_translatable_batch', array($translatable, $field_name));
          $field_operations = $translatable ? $field_operations : array_reverse($field_operations);
        }
        $operations = array_merge($operations, $field_operations);
      }
    }
  }

  // As last operation store the submitted settings.
  $operations[] = array('content_translation_save_settings', array($settings));

  $batch = array(
    'title' => t('Updating translatability for the selected fields'),
    'operations' => $operations,
    'finished' => 'content_translation_translatable_batch_done',
    'file' => drupal_get_path('module', 'content_translation') . '/content_translation.admin.inc',
  );
  batch_set($batch);
}

/**
 * Toggles translatability of the given field.
 *
 * This is called from a batch operation, but should only run once per field.
 *
 * @param bool $translatable
 *   Indicator of whether the field should be made translatable (TRUE) or
 *   untranslatble (FALSE).
 * @param string $entity_type
 *   Field entity type.
 * @param string $field_name
 *   Field machine name.
 */
function content_translation_translatable_switch($translatable, $entity_type, $field_name) {
  $field = field_info_field($entity_type, $field_name);
  if ($field['translatable'] !== $translatable) {
    $field['translatable'] = $translatable;
    $field->save();
  }
}

/**
 * Batch callback: Converts field data to or from Language::LANGCODE_NOT_SPECIFIED.
 *
 * @param bool $translatable
 *   Indicator of whether the field should be made translatable (TRUE) or
 *   untranslatble (FALSE).
 * @param string $field_name
 *   Field machine name.
 */
function content_translation_translatable_batch($translatable, $field_name, &$context) {
  // Determine the entity types to act on.
  $entity_types = array();
  foreach (field_info_instances() as $entity_type => $info) {
    foreach ($info as $bundle => $instances) {
      foreach ($instances as $instance_field_name => $instance) {
        if ($instance_field_name == $field_name) {
          $entity_types[] = $entity_type;
          break 2;
        }
      }
    }
  }

  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = 0;

    foreach ($entity_types as $entity_type) {
      $field = field_info_field($entity_type, $field_name);
      $column = isset($field['columns']['value']) ? 'value' : key($field['columns']);
      $query_field = "$field_name.$column";

      // How many entities will need processing?
      $query = \Drupal::entityQuery($entity_type);
      $count = $query
        ->exists($query_field)
        ->count()
        ->execute();

      $context['sandbox']['max'] += $count;
      $context['sandbox']['progress_entity_type'][$entity_type] = 0;
      $context['sandbox']['max_entity_type'][$entity_type] = $count;
    }

    if ($context['sandbox']['max'] === 0) {
      // Nothing to do.
      $context['finished'] = 1;
      return;
    }
  }

  foreach ($entity_types as $entity_type) {
    if ($context['sandbox']['max_entity_type'][$entity_type] === 0) {
      continue;
    }

    $info = entity_get_info($entity_type);
    $offset = $context['sandbox']['progress_entity_type'][$entity_type];
    $query = \Drupal::entityQuery($entity_type);
    $field = field_info_field($entity_type, $field_name);
    $column = isset($field['columns']['value']) ? 'value' : key($field['columns']);
    $query_field = "$field_name.$column";
    $result = $query
      ->exists($query_field)
      ->sort($info['entity_keys']['id'])
      ->range($offset, 10)
      ->execute();

    foreach (entity_load_multiple($entity_type, $result) as $id => $entity) {
      $context['sandbox']['max_entity_type'][$entity_type] -= count($result);
      $context['sandbox']['progress_entity_type'][$entity_type]++;
      $context['sandbox']['progress']++;
      $langcode = $entity->language()->id;

      // Skip process for language neutral entities.
      if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
        continue;
      }

      // We need a two-step approach while updating field translations: given
      // that field-specific update functions might rely on the stored values to
      // perform their processing first we need to store the new translations
      // and only after we can remove the old ones. Otherwise we might have data
      // loss, since the removal of the old translations might occur before the
      // new ones are stored.
      if ($translatable && isset($entity->{$field_name}[Language::LANGCODE_NOT_SPECIFIED])) {
        // If the field is being switched to translatable and has data for
        // Language::LANGCODE_NOT_SPECIFIED then we need to move the data to the right
        // language.
        $entity->{$field_name}[$langcode] = $entity->{$field_name}[Language::LANGCODE_NOT_SPECIFIED];
        // Store the original value.
        _content_translation_update_field($entity_type, $entity, $field_name);
        $entity->{$field_name}[Language::LANGCODE_NOT_SPECIFIED] = array();
        // Remove the language neutral value.
        _content_translation_update_field($entity_type, $entity, $field_name);
      }
      elseif (!$translatable && isset($entity->{$field_name}[$langcode])) {
        // The field has been marked untranslatable and has data in the entity
        // language: we need to move it to Language::LANGCODE_NOT_SPECIFIED and drop the
        // other translations.
        $entity->{$field_name}[Language::LANGCODE_NOT_SPECIFIED] = $entity->{$field_name}[$langcode];
        // Store the original value.
        _content_translation_update_field($entity_type, $entity, $field_name);
        // Remove translations.
        foreach ($entity->{$field_name} as $langcode => $items) {
          if ($langcode != Language::LANGCODE_NOT_SPECIFIED) {
            $entity->{$field_name}[$langcode] = array();
          }
        }
        _content_translation_update_field($entity_type, $entity, $field_name);
      }
      else {
        // No need to save unchanged entities.
        continue;
      }
    }
  }

  $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
}

/**
 * Stores the given field translations.
 */
function _content_translation_update_field($entity_type, EntityInterface $entity, $field_name) {
  $empty = 0;
  $translations = $entity->getTranslationLanguages();

  // Ensure that we are trying to store only valid data.
  foreach (array_keys($translations) as $langcode) {
    $items = $entity->getTranslation($langcode)->get($field_name);
    $items->filterEmptyValues();
    $empty += $items->isEmpty();
  }

  // Save the field value only if there is at least one item available,
  // otherwise any stored empty field value would be deleted. If this happens
  // the range queries would be messed up.
  if ($empty < count($translations)) {
    $entity->save();
  }
}

/**
 * Batch finished callback: Checks the exit status of the batch operation.
 */
function content_translation_translatable_batch_done($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t("Successfully changed field translation setting."));
  }
  else {
    // @todo: Do something about this case.
    drupal_set_message(t("Something went wrong while processing data. Some nodes may appear to have lost fields."), 'error');
  }
}

