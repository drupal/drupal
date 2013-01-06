<?php

/**
 * @file
 * The entity translation administration forms.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_form_FORM_ID_alter() for language_content_settings_form().
 *
 * @see translation_entity_form_language_content_settings_form_alter()
 */
function _translation_entity_form_language_content_settings_form_alter(array &$form, array &$form_state) {
  // Inject into the content language settings the translation settings if the
  // user has the required permission.
  if (!user_access('administer entity translation')) {
    return;
  }

  $default = $form['entity_types']['#default_value'];
  foreach ($default as $entity_type => $enabled) {
    $default[$entity_type] = $enabled || translation_entity_enabled($entity_type) ? $entity_type : FALSE;
  }
  $form['entity_types']['#default_value'] = $default;

  $form['#attached']['library'][] = array('translation_entity', 'drupal.translation_entity.admin');

  foreach ($form['#labels'] as $entity_type => $label) {
    foreach (entity_get_bundles($entity_type) as $bundle) {
      $form['settings'][$entity_type][$bundle]['translatable'] = array(
        '#type' => 'checkbox',
        '#default_value' => translation_entity_enabled($entity_type, $bundle),
      );

      // Here we do not want the widget to be altered and hold also the "Enable
      // translation" checkbox, which would be redundant. Hence we add this key
      // to be able to skip alterations.
      $form['settings'][$entity_type][$bundle]['settings']['language']['#translation_entity_skip_alter'] = TRUE;

      // @todo Exploit field definitions once all core entities and field types
      //   are migrated to the Entity Field API.
      foreach (field_info_instances($entity_type, $bundle) as $field_name => $instance) {
        $field = field_info_field($field_name);
        $form['settings'][$entity_type][$bundle]['fields'][$field_name] = array(
          '#label' => $instance['label'],
          '#type' => 'checkbox',
          '#default_value' => $field['translatable'],
        );
      }
    }
  }

  $form['#validate'][] = 'translation_entity_form_language_content_settings_validate';
  $form['#submit'][] = 'translation_entity_form_language_content_settings_submit';
}

/**
 * Form validation handler for translation_entity_admin_settings_form().
 *
 * @see translation_entity_admin_settings_form_submit()
 */
function translation_entity_form_language_content_settings_validate(array $form, array &$form_state) {
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
        if (language_is_locked($values['langcode']) && $values['language_hidden']) {
          foreach (language_list(LANGUAGE_LOCKED) as $language) {
            $locked_languages[] = $language->name;
          }
          form_set_error($name, t('Translation is not supported if language is always one of: @locked_languages', array('@locked_languages' => implode(', ', $locked_languages))));
        }
      }
    }
  }
}

/**
 * Form submission handler for translation_entity_admin_settings_form().
 *
 * @see translation_entity_admin_settings_form_validate()
 */
function translation_entity_form_language_content_settings_submit(array $form, array &$form_state) {
  $entity_types = $form_state['values']['entity_types'];
  $settings = &$form_state['values']['settings'];

  // If an entity type is not translatable all its bundles and fields must be
  // marked as non-translatable. Similarly, if a bundle is made non-translatable
  // all of its fields will be not translatable.
  foreach ($settings as $entity_type => &$entity_settings) {
    foreach ($entity_settings as $bundle => &$bundle_settings) {
      $bundle_settings['translatable'] = $bundle_settings['translatable'] && $entity_types[$entity_type];
      if (!empty($bundle_settings['fields'])) {
        foreach ($bundle_settings['fields'] as $field_name => $translatable) {
          $bundle_settings['fields'][$field_name] = $translatable && $bundle_settings['translatable'];
        }
      }
    }
  }

  _translation_entity_update_field_translatability($settings);
  drupal_set_message(t('Settings successfully updated.'));
}

/**
 * Stores entity translation settings.
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
function _translation_entity_update_field_translatability($settings) {
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
          $fields[$field_name] = $translatable || !empty($fields[$field_name]);
        }
      }
      // @todo Store non-configurable field settings to be able to alter their
      //   definition afterwards.
    }
  }

  $operations = array();
  foreach ($fields as $field_name => $translatable) {
    $field = field_info_field($field_name);
    if ($field['translatable'] != $translatable) {
      // If a field is untranslatable, it can have no data except under
      // LANGUAGE_NOT_SPECIFIED. Thus we need a field to be translatable before
      // we convert data to the entity language. Conversely we need to switch
      // data back to LANGUAGE_NOT_SPECIFIED before making a field
      // untranslatable lest we lose information.
      $field_operations = array(
        array('translation_entity_translatable_switch', array($translatable, $field_name)),
      );
      if (field_has_data($field)) {
        $field_operations[] = array('translation_entity_translatable_batch', array($translatable, $field_name));
        $field_operations = $translatable ? $field_operations : array_reverse($field_operations);
      }
      $operations = array_merge($operations, $field_operations);
    }
  }

  // As last operation store the submitted settings.
  $operations[] = array('translation_entity_save_settings', array($settings));

  $batch = array(
    'title' => t('Updating translatability for the selected fields'),
    'operations' => $operations,
    'finished' => 'translation_entity_translatable_batch_done',
    'file' => drupal_get_path('module', 'translation_entity') . '/translation_entity.admin.inc',
  );
  batch_set($batch);
}

/**
 * Form constructor for the confirmation of translatability switching.
 */
function translation_entity_translatable_form(array $form, array &$form_state, $field_name) {
  $field = field_info_field($field_name);
  $t_args = array('%name' => $field_name);

  $warning = t('By submitting this form these changes will apply to the %name field everywhere it is used.', $t_args);
  if ($field['translatable']) {
    $title = t('Are you sure you want to disable translation for the %name field?', $t_args);
    $warning .= "<br>" . t("<strong>All the existing translations of this field will be deleted.</strong><br>This action cannot be undone.");
  }
  else {
    $title = t('Are you sure you want to enable translation for the %name field?', $t_args);
  }

  // We need to keep some information for later processing.
  $form_state['field'] = $field;

  // Store the 'translatable' status on the client side to prevent outdated form
  // submits from toggling translatability.
  $form['translatable'] = array(
    '#type' => 'hidden',
    '#default_value' => $field['translatable'],
  );

  return confirm_form($form, $title, '', $warning);
}

/**
 * Form submission handler for translation_entity_translatable_form().
 *
 * This submit handler maintains consistency between the translatability of an
 * entity and the language under which the field data is stored. When a field is
 * marked as translatable, all the data in
 * $entity->{field_name}[LANGUAGE_NOT_SPECIFIED] is moved to
 * $entity->{field_name}[$entity_language]. When a field is marked as
 * untranslatable the opposite process occurs. Note that marking a field as
 * untranslatable will cause all of its translations to be permanently removed,
 * with the exception of the one corresponding to the entity language.
 */
function translation_entity_translatable_form_submit(array $form, array $form_state) {
  // This is the current state that we want to reverse.
  $translatable = $form_state['values']['translatable'];
  $field_name = $form_state['field']['field_name'];
  $field = field_info_field($field_name);

  if ($field['translatable'] !== $translatable) {
    // Field translatability has changed since form creation, abort.
    $t_args = array('%field_name');
    $msg = $translatable ?
      t('The field %field_name is already translatable. No change was performed.', $t_args):
      t('The field %field_name is already untranslatable. No change was performed.', $t_args);
    drupal_set_message($msg, 'warning');
    return;
  }

  // If a field is untranslatable, it can have no data except under
  // LANGUAGE_NOT_SPECIFIED. Thus we need a field to be translatable before we
  // convert data to the entity language. Conversely we need to switch data back
  // to LANGUAGE_NOT_SPECIFIED before making a field untranslatable lest we lose
  // information.
  $operations = array(
    array('translation_entity_translatable_batch', array(!$translatable, $field_name)),
    array('translation_entity_translatable_switch', array(!$translatable, $field_name)),
  );
  $operations = $translatable ? $operations : array_reverse($operations);

  $t_args = array('%field' => $field_name);
  $title = !$translatable ? t('Enabling translation for the %field field', $t_args) : t('Disabling translation for the %field field', $t_args);

  $batch = array(
    'title' => $title,
    'operations' => $operations,
    'finished' => 'translation_entity_translatable_batch_done',
    'file' => drupal_get_path('module', 'translation_entity') . '/translation_entity.admin.inc',
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
 * @param string $field_name
 *   Field machine name.
 */
function translation_entity_translatable_switch($translatable, $field_name) {
  $field = field_info_field($field_name);
  if ($field['translatable'] !== $translatable) {
    $field['translatable'] = $translatable;
    field_update_field($field);
  }
}

/**
 * Batch callback: Converts field data to or from LANGUAGE_NOT_SPECIFIED.
 *
 * @param bool $translatable
 *   Indicator of whether the field should be made translatable (TRUE) or
 *   untranslatble (FALSE).
 * @param string $field_name
 *   Field machine name.
 */
function translation_entity_translatable_batch($translatable, $field_name, &$context) {
  $field = field_info_field($field_name);
  $column = isset($field['columns']['value']) ? 'value' : key($field['columns']);
  $query_field = "$field_name.$column";

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
      // How many entities will need processing?
      $query = entity_query($entity_type);
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
    $query = entity_query($entity_type);
    $result = $query
      ->exists($query_field)
      ->sort($info['entity_keys']['id'])
      ->range($offset, 10)
      ->execute();

    foreach (entity_load_multiple($entity_type, $result) as $id => $entity) {
      $context['sandbox']['max_entity_type'][$entity_type] -= count($result);
      $context['sandbox']['progress_entity_type'][$entity_type]++;
      $context['sandbox']['progress']++;
      $langcode = $entity->language()->langcode;

      // Skip process for language neutral entities.
      if ($langcode == LANGUAGE_NOT_SPECIFIED) {
        continue;
      }

      // We need a two-step approach while updating field translations: given
      // that field-specific update functions might rely on the stored values to
      // perform their processing, see for instance file_field_update(), first
      // we need to store the new translations and only after we can remove the
      // old ones. Otherwise we might have data loss, since the removal of the
      // old translations might occur before the new ones are stored.
      if ($translatable && isset($entity->{$field_name}[LANGUAGE_NOT_SPECIFIED])) {
        // If the field is being switched to translatable and has data for
        // LANGUAGE_NOT_SPECIFIED then we need to move the data to the right
        // language.
        $entity->{$field_name}[$langcode] = $entity->{$field_name}[LANGUAGE_NOT_SPECIFIED];
        // Store the original value.
        _translation_entity_update_field($entity_type, $entity, $field_name);
        $entity->{$field_name}[LANGUAGE_NOT_SPECIFIED] = array();
        // Remove the language neutral value.
        _translation_entity_update_field($entity_type, $entity, $field_name);
      }
      elseif (!$translatable && isset($entity->{$field_name}[$langcode])) {
        // The field has been marked untranslatable and has data in the entity
        // language: we need to move it to LANGUAGE_NOT_SPECIFIED and drop the
        // other translations.
        $entity->{$field_name}[LANGUAGE_NOT_SPECIFIED] = $entity->{$field_name}[$langcode];
        // Store the original value.
        _translation_entity_update_field($entity_type, $entity, $field_name);
        // Remove translations.
        foreach ($entity->{$field_name} as $langcode => $items) {
          if ($langcode != LANGUAGE_NOT_SPECIFIED) {
            $entity->{$field_name}[$langcode] = array();
          }
        }
        _translation_entity_update_field($entity_type, $entity, $field_name);
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
function _translation_entity_update_field($entity_type, EntityInterface $entity, $field_name) {
  $empty = 0;
  $field = field_info_field($field_name);

  // Ensure that we are trying to store only valid data.
  foreach ($entity->{$field_name} as $langcode => $items) {
    $entity->{$field_name}[$langcode] = _field_filter_items($field, $entity->{$field_name}[$langcode]);
    $empty += empty($entity->{$field_name}[$langcode]);
  }

  // Save the field value only if there is at least one item available,
  // otherwise any stored empty field value would be deleted. If this happens
  // the range queries would be messed up.
  if ($empty < count($entity->{$field_name})) {
    field_attach_presave($entity_type, $entity);
    field_attach_update($entity_type, $entity);
  }
}

/**
 * Batch finished callback: Checks the exit status of the batch operation.
 */
function translation_entity_translatable_batch_done($success, $results, $operations) {
  if ($success) {
    drupal_set_message(t("Successfully changed field translation setting."));
  }
  else {
    // @todo: Do something about this case.
    drupal_set_message(t("Something went wrong while processing data. Some nodes may appear to have lost fields."), 'error');
  }
}

