<?php

namespace Drupal\system\Plugin\migrate\source\d7;

use Drupal\system\Plugin\migrate\source\Menu;

/**
 * Menu translation source from database.
 *
 * @MigrateSource(
 *   id = "d7_menu_translation",
 *   source_module = "i18n_menu"
 * )
 */
class MenuTranslation extends Menu {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query
      ->fields('i18n', [
        'lid',
        'textgroup',
        'context',
        'objectid',
        'type',
        'property',
        'objectindex',
        'format',
      ])
      ->fields('lt', [
        'lid',
        'translation',
        'language',
        'plid',
        'plural',
        'i18n_status',
      ])
      ->condition('i18n.textgroup', 'menu')
      ->isNotNull('lt.lid');

    $query->addField('m', 'language', 'm_language');
    $query->leftJoin('i18n_string', 'i18n', 'i18n.objectid = m.menu_name');
    $query->leftJoin('locales_target', 'lt', 'lt.lid = i18n.lid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'lid' => $this->t('Language string ID'),
      'language' => $this->t('Menu language'),
      'textgroup' => $this->t('A module defined group of translations'),
      'context' => $this->t('Full string ID for quick search: type:objectid:property.'),
      'objectid' => $this->t('Object ID'),
      'type' => $this->t('Object type for this string'),
      'property' => $this->t('Object property for this string'),
      'objectindex' => $this->t('Integer value of Object ID'),
      'format' => $this->t('The {filter_format}.format of the string'),
      'translation' => $this->t('Translation'),
      'plid' => $this->t('Parent lid'),
      'i18n_status' => $this->t('Translation needs update'),
    ] + parent::fields();
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = parent::getIds();
    $ids['language']['type'] = 'string';
    $ids['language']['alias'] = 'lt';
    $ids['property']['type'] = 'string';
    return $ids;
  }

}
