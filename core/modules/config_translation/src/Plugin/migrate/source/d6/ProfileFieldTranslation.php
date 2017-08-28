<?php

namespace Drupal\config_translation\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Gets i18n strings profile field source from database.
 *
 * @MigrateSource(
 *   id = "d6_profile_field_translation",
 *   source_module = "i18nprofile"
 * )
 */
class ProfileFieldTranslation extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('profile_fields', 'pf')
      ->fields('pf', ['fid', 'name'])
      ->fields('i18n', ['property'])
      ->fields('lt', ['lid', 'translation', 'language']);
    $query->leftJoin('i18n_strings', 'i18n', 'i18n.objectid = pf.name');
    $query->leftJoin('locales_target', 'lt', 'lt.lid = i18n.lid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('Profile field ID.'),
      'lid' => $this->t('Locales target language ID.'),
      'language' => $this->t('Language for this field.'),
      'translation' => $this->t('Translation of either the title or explanation.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    $ids['language']['type'] = 'string';
    $ids['lid']['type'] = 'integer';
    $ids['lid']['alias'] = 'lt';
    return $ids;
  }

}
