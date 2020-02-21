<?php

namespace Drupal\language\Plugin\migrate\source\d7;

use Drupal\system\Plugin\migrate\source\Menu;

/**
 * Drupal 7 i18n menu links source from database.
 *
 * @MigrateSource(
 *   id = "d7_language_content_settings_menu",
 *   source_module = "i18n_menu"
 * )
 */
class LanguageContentSettingsMenu extends Menu {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if ($this->getDatabase()
      ->schema()
      ->fieldExists('menu_custom', 'i18n_mode')) {
      $query->addField('m', 'language');
      $query->addField('m', 'i18n_mode');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['language'] = $this->t('i18n language');
    $fields['i18n_mode'] = $this->t('i18n mode');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = parent::getIds();
    $ids['language']['type'] = 'string';
    return $ids;
  }

}
