<?php

namespace Drupal\menu_link_content\Plugin\migrate\source\d7;

use Drupal\menu_link_content\Plugin\migrate\source\MenuLink;
use Drupal\migrate\Row;

// cspell:ignore mlid tsid

/**
 * Drupal 7 localized menu link translations source from database.
 *
 * @MigrateSource(
 *   id = "d7_menu_link_localized",
 *   source_module = "i18n_menu"
 * )
 */
class MenuLinkLocalized extends MenuLink {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    $query->condition('ml.i18n_tsid', '0', '<>');
    // The first row in a translation set is the source.
    $query->orderBy('ml.i18n_tsid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'ml_language' => $this->t('Menu link ID of the source language menu link.'),
      'skip_source_translation' => $this->t('Menu link description translation.'),
    ];
    return parent::fields() + $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('skip_source_translation', TRUE);
    // Get the mlid for the source menu_link.
    $source_mlid = $this->select('menu_links', 'ml')
      ->fields('ml', ['mlid'])
      ->condition('i18n_tsid', $row->getSourceProperty('i18n_tsid'))
      ->orderBy('mlid')
      ->range(0, 1)
      ->execute()
      ->fetchField();
    if ($source_mlid == $row->getSourceProperty('mlid')) {
      $row->setSourceProperty('skip_source_translation', FALSE);
    }
    $row->setSourceProperty('mlid', $source_mlid);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['language']['type'] = 'string';
    $ids['language']['alias'] = 'ml';
    return parent::getIds() + $ids;
  }

}
