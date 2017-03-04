<?php

namespace Drupal\block_content\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 custom block source from database.
 *
 * @MigrateSource(
 *   id = "d7_block_custom"
 * )
 */
class BlockCustom extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('block_custom', 'b')->fields('b');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'bid' => $this->t('The numeric identifier of the block/box'),
      'body' => $this->t('The block/box content'),
      'info' => $this->t('Admin title of the block/box.'),
      'format' => $this->t('Input format of the custom block/box content.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['bid']['type'] = 'integer';
    return $ids;
  }

}
