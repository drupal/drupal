<?php

namespace Drupal\content_translation\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 Entity Translation settings (variables) from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_entity_translation_settings",
 *   source_module = "entity_translation"
 * )
 */
class EntityTranslationSettings extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Query all meaningful variables for entity translation.
    $query = $this->select('variable', 'v')
      ->fields('v', ['name', 'value']);
    $condition = $query->orConditionGroup()
      // The 'entity_translation_entity_types' variable tells us which entity
      // type uses entity translation.
      ->condition('name', 'entity_translation_entity_types')
      // The 'entity_translation_taxonomy' variable tells us which taxonomy
      // vocabulary uses entity_translation.
      ->condition('name', 'entity_translation_taxonomy')
      // The 'entity_translation_settings_%' variables give us the entity
      // translation settings for each entity type and each bundle.
      ->condition('name', 'entity_translation_settings_%', 'LIKE')
      // The 'language_content_type_%' variables tells us which node type and
      // which comment type uses entity translation.
      ->condition('name', 'language_content_type_%', 'LIKE');
    $query->condition($condition);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $results = array_map('unserialize', $this->prepareQuery()->execute()->fetchAllKeyed());
    $rows = [];

    // Find out which entity type uses entity translation by looking at the
    // 'entity_translation_entity_types' variable.
    $entity_types = array_filter($results['entity_translation_entity_types']);

    // If no entity type uses entity translation, there's nothing to do.
    if (empty($entity_types)) {
      return new \ArrayIterator($rows);
    }

    // Find out which node type uses entity translation by looking at the
    // 'language_content_type_%' variables.
    $node_types = [];
    foreach ($results as $name => $value) {
      if (preg_match('/^language_content_type_(.+)$/', $name, $matches) && (int) $value === 4) {
        $node_types[] = $matches[1];
      }
    }

    // Find out which vocabulary uses entity translation by looking at the
    // 'entity_translation_taxonomy' variable.
    $vocabularies = [];
    if (isset($results['entity_translation_taxonomy']) && is_array($results['entity_translation_taxonomy'])) {
      $vocabularies = array_keys(array_filter($results['entity_translation_taxonomy']));
    }

    if (in_array('node', $entity_types, TRUE) && !empty($node_types)) {
      // For each node type that uses entity translation, check if a
      // settings variable exists for that node type, otherwise use default
      // values.
      foreach ($node_types as $node_type) {
        $settings = $results['entity_translation_settings_node__' . $node_type] ?? [];
        $rows[] = [
          'id' => 'node.' . $node_type,
          'target_entity_type_id' => 'node',
          'target_bundle' => $node_type,
          'default_langcode' => $settings['default_language'] ?? 'und',
          // The Drupal 7 'hide_language_selector' configuration has become
          // 'language_alterable' in Drupal 8 so we need to negate the value we
          // receive from the source. The Drupal 7 'hide_language_selector'
          // default value for the node entity type was FALSE so in Drupal 8 it
          // should be set to TRUE, unlike the other entity types for which
          // it's the opposite.
          'language_alterable' => isset($settings['hide_language_selector']) ? (bool) !$settings['hide_language_selector'] : TRUE,
          'untranslatable_fields_hide' => isset($settings['shared_fields_original_only']) ? (bool) $settings['shared_fields_original_only'] : FALSE,
        ];
      }
    }

    if (in_array('comment', $entity_types, TRUE) && !empty($node_types)) {
      // A comment type uses entity translation if the associated node type
      // uses it. So, for each node type that uses entity translation, check
      // if a settings variable exists for that comment type, otherwise use
      // default values.
      foreach ($node_types as $node_type) {
        $settings = $results['entity_translation_settings_comment__comment_node_' . $node_type] ?? [];
        // Forum uses a hardcoded comment type name, so make sure we use it
        // when we're dealing with forum comment type.
        $bundle = $node_type == 'forum' ? 'comment_forum' : 'comment_node_' . $node_type;
        $rows[] = [
          'id' => 'comment.' . $bundle,
          'target_entity_type_id' => 'comment',
          'target_bundle' => $bundle,
          'default_langcode' => $settings['default_language'] ?? 'xx-et-current',
          // The Drupal 7 'hide_language_selector' configuration has become
          // 'language_alterable' in Drupal 8 so we need to negate the value we
          // receive from the source. The Drupal 7 'hide_language_selector'
          // default value for the comment entity type was TRUE so in Drupal 8
          // it should be set to FALSE.
          'language_alterable' => isset($settings['hide_language_selector']) ? (bool) !$settings['hide_language_selector'] : FALSE,
          'untranslatable_fields_hide' => isset($settings['shared_fields_original_only']) ? (bool) $settings['shared_fields_original_only'] : FALSE,
        ];
      }
    }

    if (in_array('taxonomy_term', $entity_types, TRUE) && !empty($vocabularies)) {
      // For each vocabulary that uses entity translation, check if a
      // settings variable exists for that vocabulary, otherwise use default
      // values.
      foreach ($vocabularies as $vocabulary) {
        $settings = $results['entity_translation_settings_taxonomy_term__' . $vocabulary] ?? [];
        $rows[] = [
          'id' => 'taxonomy_term.' . $vocabulary,
          'target_entity_type_id' => 'taxonomy_term',
          'target_bundle' => $vocabulary,
          'default_langcode' => $settings['default_language'] ?? 'xx-et-default',
          // The Drupal 7 'hide_language_selector' configuration has become
          // 'language_alterable' in Drupal 8 so we need to negate the value we
          // receive from the source. The Drupal 7 'hide_language_selector'
          // default value for the taxonomy_term entity type was TRUE so in
          // Drupal 8 it should be set to FALSE.
          'language_alterable' => isset($settings['hide_language_selector']) ? (bool) !$settings['hide_language_selector'] : FALSE,
          'untranslatable_fields_hide' => isset($settings['shared_fields_original_only']) ? (bool) $settings['shared_fields_original_only'] : FALSE,
        ];
      }
    }

    if (in_array('user', $entity_types, TRUE)) {
      // User entity type is not bundleable. Check if a settings variable
      // exists, otherwise use default values.
      $settings = $results['entity_translation_settings_user__user'] ?? [];
      $rows[] = [
        'id' => 'user.user',
        'target_entity_type_id' => 'user',
        'target_bundle' => 'user',
        'default_langcode' => $settings['default_language'] ?? 'xx-et-default',
        // The Drupal 7 'hide_language_selector' configuration has become
        // 'language_alterable' in Drupal 8 so we need to negate the value we
        // receive from the source. The Drupal 7 'hide_language_selector'
        // default value for the user entity type was TRUE so in Drupal 8 it
        // should be set to FALSE.
        'language_alterable' => isset($settings['hide_language_selector']) ? (bool) !$settings['hide_language_selector'] : FALSE,
        'untranslatable_fields_hide' => isset($settings['shared_fields_original_only']) ? (bool) $settings['shared_fields_original_only'] : FALSE,
      ];
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('The configuration ID'),
      'target_entity_type_id' => $this->t('The target entity type ID'),
      'target_bundle' => $this->t('The target bundle'),
      'default_langcode' => $this->t('The default language'),
      'language_alterable' => $this->t('Whether to show language selector on create and edit pages'),
      'untranslatable_fields_hide' => $this->t('Whether to hide non translatable fields on translation forms'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    // Since the number of variables we fetch with query() does not match the
    // actual number of rows generated by initializeIterator(), we need to
    // override count() to return the correct count.
    return (int) $this->initializeIterator()->count();
  }

}
