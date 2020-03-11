<?php

/**
 * @file
 * Post update functions for the Menu link content module.
 */

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\menu_link_content\MenuLinkContentStorage;

/**
 * Update custom menu links to be revisionable.
 */
function menu_link_content_post_update_make_menu_link_content_revisionable(&$sandbox) {
  $finished = _menu_link_content_post_update_make_menu_link_content_revisionable__fix_default_langcode($sandbox);
  if (!$finished) {
    $sandbox['#finished'] = 0;
    return NULL;
  }

  $definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  /** @var \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface $last_installed_schema_repository */
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');

  $entity_type = $definition_update_manager->getEntityType('menu_link_content');
  $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('menu_link_content');

  // Update the entity type definition.
  $entity_keys = $entity_type->getKeys();
  $entity_keys['revision'] = 'revision_id';
  $entity_keys['revision_translation_affected'] = 'revision_translation_affected';
  $entity_type->set('entity_keys', $entity_keys);
  $entity_type->set('revision_table', 'menu_link_content_revision');
  $entity_type->set('revision_data_table', 'menu_link_content_field_revision');
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
  $field_storage_definitions['title']->setRevisionable(TRUE);
  $field_storage_definitions['description']->setRevisionable(TRUE);
  $field_storage_definitions['link']->setRevisionable(TRUE);
  $field_storage_definitions['external']->setRevisionable(TRUE);
  $field_storage_definitions['enabled']->setRevisionable(TRUE);
  $field_storage_definitions['changed']->setRevisionable(TRUE);

  $field_storage_definitions['revision_id'] = BaseFieldDefinition::create('integer')
    ->setName('revision_id')
    ->setTargetEntityTypeId('menu_link_content')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision ID'))
    ->setReadOnly(TRUE)
    ->setSetting('unsigned', TRUE);

  $field_storage_definitions['revision_default'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_default')
    ->setTargetEntityTypeId('menu_link_content')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Default revision'))
    ->setDescription(new TranslatableMarkup('A flag indicating whether this was a default revision when it was saved.'))
    ->setStorageRequired(TRUE)
    ->setInternal(TRUE)
    ->setTranslatable(FALSE)
    ->setRevisionable(TRUE);

  $field_storage_definitions['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
    ->setName('revision_translation_affected')
    ->setTargetEntityTypeId('menu_link_content')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision translation affected'))
    ->setDescription(new TranslatableMarkup('Indicates if the last edit of a translation belongs to current revision.'))
    ->setReadOnly(TRUE)
    ->setRevisionable(TRUE)
    ->setTranslatable(TRUE);

  $field_storage_definitions['revision_created'] = BaseFieldDefinition::create('created')
    ->setName('revision_created')
    ->setTargetEntityTypeId('menu_link_content')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision create time'))
    ->setDescription(new TranslatableMarkup('The time that the current revision was created.'))
    ->setRevisionable(TRUE);
  $field_storage_definitions['revision_user'] = BaseFieldDefinition::create('entity_reference')
    ->setName('revision_user')
    ->setTargetEntityTypeId('menu_link_content')
    ->setTargetBundle(NULL)
    ->setLabel(new TranslatableMarkup('Revision user'))
    ->setDescription(new TranslatableMarkup('The user ID of the author of the current revision.'))
    ->setSetting('target_type', 'user')
    ->setRevisionable(TRUE);
  $field_storage_definitions['revision_log_message'] = BaseFieldDefinition::create('string_long')
    ->setName('revision_log_message')
    ->setTargetEntityTypeId('menu_link_content')
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
      return new PluralTranslatableMarkup($count, 'Custom menu links have been converted to be revisionable. One menu link with data integrity issues was restored. More details have been <a href=":url">logged</a>.', 'Custom menu links have been converted to be revisionable. @count menu links with data integrity issues were restored. More details have been <a href=":url">logged</a>.', $args);
    }
    else {
      return new PluralTranslatableMarkup($count, 'Custom menu links have been converted to be revisionable. One menu link with data integrity issues was restored. More details have been logged.', 'Custom menu links have been converted to be revisionable. @count menu links with data integrity issues were restored. More details have been logged.');
    }
  }
  else {
    return t('Custom menu links have been converted to be revisionable.');
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
function _menu_link_content_post_update_make_menu_link_content_revisionable__fix_default_langcode(array &$sandbox) {
  if (!empty($sandbox['data_fix']['default_langcode']['finished'])) {
    return TRUE;
  }

  $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  if (!$storage instanceof MenuLinkContentStorage) {
    return TRUE;
  }
  elseif (!isset($sandbox['data_fix']['default_langcode']['last_id'])) {
    $sandbox['data_fix']['default_langcode'] = [
      'last_id' => 0,
      'processed' => 0,
    ];
  }

  $database = \Drupal::database();
  $data_table_name = 'menu_link_content_data';
  $last_id = $sandbox['data_fix']['default_langcode']['last_id'];
  $limit = Settings::get('update_sql_batch_size', 200);

  // Detect records in the data table matching the base table language, but
  // having the "default_langcode" flag set to with 0, which is not supported.
  $query = $database->select($data_table_name, 'd');
  $query->leftJoin('menu_link_content', 'b', 'd.id = b.id AND d.langcode = b.langcode AND d.default_langcode = 0');
  $result = $query->fields('d', ['id', 'langcode'])
    ->condition('d.id', $last_id, '>')
    ->isNotNull('b.id')
    ->orderBy('d.id')
    ->range(0, $limit)
    ->execute();

  foreach ($result as $record) {
    $sandbox['data_fix']['default_langcode']['last_id'] = $record->id;

    // We need to exclude any menu link already having also a data table record
    // with the "default_langcode" flag set to 1, because this is a data
    // integrity issue that cannot be fixed automatically. However the latter
    // will not make the update fail.
    $has_default_langcode = (bool) $database->select($data_table_name, 'd')
      ->fields('d', ['id'])
      ->condition('d.id', $record->id)
      ->condition('d.default_langcode', 1)
      ->range(0, 1)
      ->execute()
      ->fetchField();

    if ($has_default_langcode) {
      continue;
    }

    $database->update($data_table_name)
      ->fields(['default_langcode' => 1])
      ->condition('id', $record->id)
      ->condition('langcode', $record->langcode)
      ->execute();

    $sandbox['data_fix']['default_langcode']['processed']++;

    \Drupal::logger('update')
      ->warning('The menu link with ID @id had data integrity issues and was restored.', ['@id' => $record->id]);
  }

  $finished = $sandbox['data_fix']['default_langcode']['last_id'] === $last_id;
  $sandbox['data_fix']['default_langcode']['finished'] = $finished;

  return $finished;
}
