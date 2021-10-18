<?php

namespace Drupal\field\Plugin\migrate\source\d6;

/**
 * Drupal 6 i18n field option labels source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d6_field_option_translation",
 *   source_module = "i18ncck"
 * )
 */
class FieldOptionTranslation extends Field {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Get the fields that have field options translations.
    $query = $this->select('i18n_strings', 'i18n')
      ->fields('i18n')
      ->fields('lt', [
        'translation',
        'language',
        'plid',
        'plural',
      ])
      ->condition('i18n.type', 'field')
      ->condition('property', 'option\_%', 'LIKE');
    $query->innerJoin('locales_target', 'lt', '[lt].[lid] = [i18n].[lid]');
    $query->leftjoin('content_node_field', 'cnf', '[cnf].[field_name] = [i18n].[objectid]');
    $query->addField('cnf', 'field_name');
    $query->addField('cnf', 'global_settings');
    // Minimise changes to the d6_field_option_translation.yml, which is copied
    // from d6_field.yml, by ensuring the 'type' property is from
    // content_node_field table.
    $query->addField('cnf', 'type');
    $query->addField('i18n', 'type', 'i18n_type');

    // The i18n_string module adds a status column to locale_target. It was
    // originally 'status' in a later revision it was named 'i18n_status'.
    /** @var \Drupal\Core\Database\Schema $db */
    if ($this->getDatabase()->schema()->fieldExists('locales_target', 'status')) {
      $query->addField('lt', 'status', 'i18n_status');
    }
    if ($this->getDatabase()->schema()->fieldExists('locales_target', 'i18n_status')) {
      $query->addField('lt', 'i18n_status');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'property' => $this->t('Option ID.'),
      'objectid' => $this->t('Object ID'),
      'objectindex' => $this->t('Integer value of Object ID'),
      'format' => $this->t('The input format used by this string'),
      'lid' => $this->t('Source string ID'),
      'language' => $this->t('Language code'),
      'translation' => $this->t('Translation of the option'),
      'plid' => $this->t('Parent lid'),
      'plural' => $this->t('Plural index number in case of plural strings'),
    ];
    return parent::fields() + $fields;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return parent::getIds() +
      [
        'language' => ['type' => 'string'],
        'property' => ['type' => 'string'],
      ];
  }

}
