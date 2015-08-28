<?php

/**
 * @file
 * Contains \Drupal\contact\Plugin\migrate\source\d6\ContactCategory.
 */

namespace Drupal\contact\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Drupal 6 contact category source from database.
 *
 * @MigrateSource(
 *   id = "d6_contact_category",
 *   source_provider = "contact"
 * )
 */
class ContactCategory extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('contact', 'c')
      ->fields('c', array(
        'cid',
        'category',
        'recipients',
        'reply',
        'weight',
        'selected',
      )
    );
    $query->orderBy('cid');
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
    return array(
      'cid' => $this->t('Primary Key: Unique category ID.'),
      'category' => $this->t('Category name.'),
      'recipients' => $this->t('Comma-separated list of recipient email addresses.'),
      'reply' => $this->t('Text of the auto-reply message.'),
      'weight' => $this->t("The category's weight."),
      'selected' => $this->t('Flag to indicate whether or not category is selected by default. (1 = Yes, 0 = No)'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['cid']['type'] = 'integer';
    return $ids;
  }

}
