<?php

namespace Drupal\contact\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Contact category source from database.
 *
 * @MigrateSource(
 *   id = "contact_category",
 *   source_provider = "contact"
 * )
 */
class ContactCategory extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('contact', 'c')
      ->fields('c', [
        'cid',
        'category',
        'recipients',
        'reply',
        'weight',
        'selected',
      ]
    );
    $query->orderBy('c.cid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('recipients', explode(',', $row->getSourceProperty('recipients')));
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'cid' => $this->t('Primary Key: Unique category ID.'),
      'category' => $this->t('Category name.'),
      'recipients' => $this->t('Comma-separated list of recipient email addresses.'),
      'reply' => $this->t('Text of the auto-reply message.'),
      'weight' => $this->t("The category's weight."),
      'selected' => $this->t('Flag to indicate whether or not category is selected by default. (1 = Yes, 0 = No)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['cid']['type'] = 'integer';
    return $ids;
  }

}
