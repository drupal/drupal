<?php

namespace Drupal\field\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

// cspell:ignore objectid objectindex plid textgroup

/**
 * Drupal 7 i18n field label and description source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_field_instance_label_description_translation",
 *   source_module = "i18n_field"
 * )
 */
class FieldLabelDescriptionTranslation extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Get translations for field labels and descriptions.
    $query = $this->select('i18n_string', 'i18n')
      ->fields('i18n')
      ->fields('lt', [
        'lid',
        'translation',
        'language',
        'plid',
        'plural',
        'i18n_status',
      ])
      ->fields('fci', [
        'id',
        'field_id',
        'field_name',
        'entity_type',
        'bundle',
        'data',
        'deleted',
      ])
      ->condition('i18n.textgroup', 'field');
    $condition = $query->orConditionGroup()
      ->condition('textgroup', 'field')
      ->condition('objectid', '#allowed_values', '!=');
    $query->condition($condition);
    $query->innerJoin('locales_target', 'lt', '[lt].[lid] = [i18n].[lid]');

    $query->leftJoin('field_config_instance', 'fci', '[fci].[bundle] = [i18n].[objectid] AND [fci].[field_name] = [i18n].[type]');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'lid' => $this->t('Locales target language ID.'),
      'textgroup' => $this->t('A module defined group of translations'),
      'context' => $this->t('Full string ID for quick search: type:objectid:property.'),
      'objectid' => $this->t('Object ID'),
      'type' => $this->t('Object type for this string'),
      'property' => $this->t('Object property for this string'),
      'objectindex' => $this->t('Integer value of Object ID'),
      'format' => $this->t('The {filter_format}.format of the string'),
      'translation' => $this->t('Translation'),
      'language' => $this->t('Language code'),
      'plid' => $this->t('Parent lid'),
      'plural' => $this->t('Plural index number'),
      'i18n_status' => $this->t('Translation needs update'),
      'id' => $this->t('The field instance ID.'),
      'field_id' => $this->t('The field ID.'),
      'field_name' => $this->t('The field name.'),
      'entity_type' => $this->t('The entity type.'),
      'bundle' => $this->t('The entity bundle.'),
      'data' => $this->t('The field instance data.'),
      'deleted' => $this->t('Deleted'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['property']['type'] = 'string';
    $ids['language']['type'] = 'string';
    $ids['lid']['type'] = 'integer';
    $ids['lid']['alias'] = 'lt';
    return $ids;
  }

}
