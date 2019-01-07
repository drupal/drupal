<?php

namespace Drupal\user\Plugin\migrate\source\d6;

use Drupal\user\Plugin\migrate\source\ProfileField;

/**
 * Gets field option label translations.
 *
 * @MigrateSource(
 *   id = "d6_profile_field_option_translation",
 *   source_module = "i18nprofile"
 * )
 */
class ProfileFieldOptionTranslation extends ProfileField {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query
      ->fields('i18n', ['property', 'objectid'])
      ->fields('lt', ['translation', 'language'])
      ->condition('i18n.type', 'field')
      ->condition('property', 'options')
      ->isNotNull('translation');
    $query->leftjoin('i18n_strings', 'i18n', 'pf.name = i18n.objectid');
    $query->leftJoin('locales_target', 'lt', 'lt.lid = i18n.lid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() +
      [
        'property' => $this->t('Option ID.'),
        'objectid' => $this->t('Field name'),
        'language' => $this->t('Language for this field.'),
        'translation' => $this->t('Translation of either the title or explanation.'),
      ];
  }

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
