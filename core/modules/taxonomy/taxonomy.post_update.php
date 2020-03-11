<?php

/**
 * @file
 * Post update functions for Taxonomy.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\taxonomy\TermStorage;
use Drupal\views\ViewExecutable;

/**
 * Clear caches due to updated taxonomy entity views data.
 */
function taxonomy_post_update_clear_views_data_cache() {
  // An empty update will flush caches.
}

/**
 * Clear entity_bundle_field_definitions cache for new parent field settings.
 */
function taxonomy_post_update_clear_entity_bundle_field_definitions_cache() {
  // An empty update will flush caches.
}

/**
 * Add a 'published' = TRUE filter for all Taxonomy term views and converts
 * existing ones that were using the 'content_translation_status' field.
 */
function taxonomy_post_update_handle_publishing_status_addition_in_views(&$sandbox = NULL) {
  // If Views is not installed, there is nothing to do.
  if (!\Drupal::moduleHandler()->moduleExists('views')) {
    return;
  }

  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type = $definition_update_manager->getEntityType('taxonomy_term');
  $published_key = $entity_type->getKey('published');

  $status_filter = [
    'id' => 'status',
    'table' => 'taxonomy_term_field_data',
    'field' => $published_key,
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'operator' => '=',
    'value' => '1',
    'group' => 1,
    'exposed' => FALSE,
    'expose' => [
      'operator_id' => '',
      'label' => '',
      'description' => '',
      'use_operator' => FALSE,
      'operator' => '',
      'identifier' => '',
      'required' => FALSE,
      'remember' => FALSE,
      'multiple' => FALSE,
      'remember_roles' => [
        'authenticated' => 'authenticated',
        'anonymous' => '0',
        'administrator' => '0',
      ],
    ],
    'is_grouped' => FALSE,
    'group_info' => [
      'label' => '',
      'description' => '',
      'identifier' => '',
      'optional' => TRUE,
      'widget' => 'select',
      'multiple' => FALSE,
      'remember' => FALSE,
      'default_group' => 'All',
      'default_group_multiple' => [],
      'group_items' => [],
    ],
    'entity_type' => 'taxonomy_term',
    'entity_field' => $published_key,
    'plugin_id' => 'boolean',
  ];

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) use ($published_key, $status_filter) {
    /** @var \Drupal\views\ViewEntityInterface $view */
    // Only alter taxonomy term views.
    if ($view->get('base_table') !== 'taxonomy_term_field_data') {
      return FALSE;
    }

    $displays = $view->get('display');
    foreach ($displays as $display_name => &$display) {
      // Update any existing 'content_translation_status fields.
      $fields = isset($display['display_options']['fields']) ? $display['display_options']['fields'] : [];
      foreach ($fields as $id => $field) {
        if (isset($field['field']) && $field['field'] == 'content_translation_status') {
          $fields[$id]['field'] = $published_key;
        }
      }
      $display['display_options']['fields'] = $fields;

      // Update any existing 'content_translation_status sorts.
      $sorts = isset($display['display_options']['sorts']) ? $display['display_options']['sorts'] : [];
      foreach ($sorts as $id => $sort) {
        if (isset($sort['field']) && $sort['field'] == 'content_translation_status') {
          $sorts[$id]['field'] = $published_key;
        }
      }
      $display['display_options']['sorts'] = $sorts;

      // Update any existing 'content_translation_status' filters or add a new
      // one if necessary.
      $filters = isset($display['display_options']['filters']) ? $display['display_options']['filters'] : [];
      $has_status_filter = FALSE;
      foreach ($filters as $id => $filter) {
        if (isset($filter['field']) && $filter['field'] == 'content_translation_status') {
          $filters[$id]['field'] = $published_key;
          $has_status_filter = TRUE;
        }
      }

      if (!$has_status_filter) {
        $status_filter['id'] = ViewExecutable::generateHandlerId($published_key, $filters);
        $filters[$status_filter['id']] = $status_filter;
      }
      $display['display_options']['filters'] = $filters;
    }
    $view->set('display', $displays);

    return TRUE;
  });
}

/**
 * Remove the 'hierarchy' property from vocabularies.
 */
function taxonomy_post_update_remove_hierarchy_from_vocabularies(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'taxonomy_vocabulary', function () {
    return TRUE;
  });
}

/**
 * Update taxonomy terms to be revisionable.
 */
function taxonomy_post_update_make_taxonomy_term_revisionable(&$sandbox) {
  $finished = _taxonomy_post_update_make_taxonomy_term_revisionable__fix_default_langcode($sandbox);
  if (!$finished) {
    $sandbox['#finished'] = 0;
    return NULL;
  }

  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');

  $entity_type = $definition_update_manager->getEntityType('taxonomy_term');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('taxonomy_term');

  // Update the entity type definition.
  $entity_keys = $entity_type->getKeys();
  $entity_keys['revision'] = 'revision_id';
  $entity_keys['revision_translation_affected'] = 'revision_translation_affected';
  $entity_type->set('entity_keys', $entity_keys);
  $entity_type->set('revision_table', 'taxonomy_term_revision');
  $entity_type->set('revision_data_table', 'taxonomy_term_field_revision');
  $revision_metadata_keys = [
    'revision_default' => 'revision_default',
    'revision_user' => 'revision_user',
    'revision_created' => 'revision_created',
    'revision_log_message' => 'revision_log_message',
  ];
  $entity_type->set('revision_metadata_keys', $revision_metadata_keys);

  // Update the field storage definitions and add the new ones required by a
  // revisionable entity type.
  $field_storage_definitions['langcode']->setRevisionable(TRUE);
  $field_storage_definitions['name']->setRevisionable(TRUE);
  $field_storage_definitions['description']->setRevisionable(TRUE);
  $field_storage_definitions['changed']->setRevisionable(TRUE);

  $field_storage_definitions['revision_id'] = BaseFieldDefinition::create('integer')
    ->setName('revision_id')
    ->setTargetEntityTypeId('taxonomy_term')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision ID'))
    ->setReadOnly(TRUE)
    ->setSetting('unsigned', TRUE);

  $field_storage_definitions['revision_default'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_default')
    ->setTargetEntityTypeId('taxonomy_term')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Default revision'))
    ->setDescription(new TranslatableMarkup('A flag indicating whether this was a default revision when it was saved.'))
    ->setStorageRequired(TRUE)
    ->setInternal(TRUE)
    ->setTranslatable(FALSE)
    ->setRevisionable(TRUE);

  $field_storage_definitions['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_translation_affected')
    ->setTargetEntityTypeId('taxonomy_term')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision translation affected'))
    ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
    ->setReadOnly(TRUE)
    ->setRevisionable(TRUE)
    ->setTranslatable(TRUE);

  $field_storage_definitions['revision_created'] = BaseFieldDefinition::create('created')
    ->setName('revision_created')
    ->setTargetEntityTypeId('taxonomy_term')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision create time'))
    ->setDescription(new TranslatableMarkup('The time that the current revision was created.'))
    ->setRevisionable(TRUE);
  $field_storage_definitions['revision_user'] = BaseFieldDefinition::create('entity_reference')
    ->setName('revision_user')
    ->setTargetEntityTypeId('taxonomy_term')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision user'))
    ->setDescription(new TranslatableMarkup('The user ID of the author of the current revision.'))
    ->setSetting('target_type', 'user')
    ->setRevisionable(TRUE);
  $field_storage_definitions['revision_log_message'] = BaseFieldDefinition::create('string_long')
    ->setName('revision_log_message')
    ->setTargetEntityTypeId('taxonomy_term')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision log message'))
    ->setDescription(new TranslatableMarkup('Briefly describe the changes you have made.'))
    ->setRevisionable(TRUE)
    ->setDefaultValue('');

  $definition_update_manager->updateFieldableEntityType($entity_type, $field_storage_definitions, $sandbox);

  if (!empty($sandbox['data_fix']['default_langcode']['processed'])) {
    $count = $sandbox['data_fix']['default_langcode']['processed'];
    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      // @todo Simplify with https://www.drupal.org/node/2548095
      $base_url = str_replace('/update.php', '', \Drupal::request()->getBaseUrl());
      $args = [
        ':url' => Url::fromRoute('dblog.overview', [], ['query' => ['type' => ['update'], 'severity' => [RfcLogLevel::WARNING]]])
          ->setOption('base_url', $base_url)
          ->toString(TRUE)
          ->getGeneratedUrl(),
      ];
      return new PluralTranslatableMarkup($count, 'Taxonomy terms have been converted to be revisionable. One term with data integrity issues was restored. More details have been <a href=":url">logged</a>.', 'Taxonomy terms have been converted to be revisionable. @count terms with data integrity issues were restored. More details have been <a href=":url">logged</a>.', $args);
    }
    else {
      return new PluralTranslatableMarkup($count, 'Taxonomy terms have been converted to be revisionable. One term with data integrity issues was restored. More details have been logged.', 'Taxonomy terms have been converted to be revisionable. @count terms with data integrity issues were restored. More details have been logged.');
    }
  }
  else {
    return t('Taxonomy terms have been converted to be revisionable.');
  }
}

/**
 * Fixes recoverable data integrity issues in the "default_langcode" field.
 *
 * @param array $sandbox
 *   The update sandbox array.
 *
 * @return bool
 *   TRUE if the operation was finished, FALSE otherwise.
 *
 * @internal
 */
function _taxonomy_post_update_make_taxonomy_term_revisionable__fix_default_langcode(array &$sandbox) {
  if (!empty($sandbox['data_fix']['default_langcode']['finished'])) {
    return TRUE;
  }

  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  if (!$storage instanceof TermStorage) {
    $sandbox['data_fix']['default_langcode']['finished'] = TRUE;
    return TRUE;
  }
  elseif (!isset($sandbox['data_fix']['default_langcode'])) {
    $sandbox['data_fix']['default_langcode'] = [
      'last_id' => 0,
      'processed' => 0,
    ];
  }

  $database = \Drupal::database();
  $data_table_name = 'taxonomy_term_field_data';
  $last_id = $sandbox['data_fix']['default_langcode']['last_id'];
  $limit = Settings::get('update_sql_batch_size', 200);

  // Detect records in the data table matching the base table language, but
  // having the "default_langcode" flag set to with 0, which is not supported.
  $query = $database->select($data_table_name, 'd');
  $query->leftJoin('taxonomy_term_data', 'b', 'd.tid = b.tid AND d.langcode = b.langcode AND d.default_langcode = 0');
  $result = $query->fields('d', ['tid', 'langcode'])
    ->condition('d.tid', $last_id, '>')
    ->isNotNull('b.tid')
    ->orderBy('d.tid')
    ->range(0, $limit)
    ->execute();

  foreach ($result as $record) {
    $sandbox['data_fix']['default_langcode']['last_id'] = $record->tid;

    // We need to exclude any term already having also a data table record with
    // the "default_langcode" flag set to 1, because this is a data integrity
    // issue that cannot be fixed automatically. However the latter will not
    // make the update fail.
    $has_default_langcode = (bool) $database->select($data_table_name, 'd')
      ->fields('d', ['tid'])
      ->condition('d.tid', $record->tid)
      ->condition('d.default_langcode', 1)
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($has_default_langcode) {
      continue;
    }

    $database->update($data_table_name)
      ->fields(['default_langcode' => 1])
      ->condition('tid', $record->tid)
      ->condition('langcode', $record->langcode)
      ->execute();

    $sandbox['data_fix']['default_langcode']['processed']++;

    \Drupal::logger('update')
      ->warning('The term with ID @id had data integrity issues and was restored.', ['@id' => $record->tid]);
  }

  $finished = $sandbox['data_fix']['default_langcode']['last_id'] === $last_id;
  $sandbox['data_fix']['default_langcode']['finished'] = $finished;

  return $finished;
}

/**
 * Add status with settings to all form displays for taxonomy entities.
 */
function taxonomy_post_update_configure_status_field_widget(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_form_display', function (EntityDisplayInterface $entity_form_display) {
    // Only update taxonomy term entity form displays with no existing options
    // for the status field.
    if ($entity_form_display->getTargetEntityTypeId() == 'taxonomy_term' && empty($entity_form_display->getComponent('status'))) {
      $entity_form_display->setComponent('status', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
      ]);
      return TRUE;
    }
    return FALSE;
  });
}
