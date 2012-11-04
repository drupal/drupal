<?php

/**
 * @file
 * The entity translation administration forms.
 */

use Drupal\Core\Entity\EntityInterface;

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
  // LANGUAGE_NOT_SPECIFIED. Thus we need a field to be translatable before we convert
  // data to the entity language. Conversely we need to switch data back to
  // LANGUAGE_NOT_SPECIFIED before making a field untranslatable lest we lose
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

  if ($field['translatable'] === $translatable) {
    return;
  }

  $field['translatable'] = $translatable;
  field_update_field($field);
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
  $entity_types = array();

  // Determine the entity types to act on.
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
        ->exists($field_name)
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
      ->exists($field_name)
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

