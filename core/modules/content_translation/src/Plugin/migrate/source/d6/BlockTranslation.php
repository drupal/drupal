<?php

namespace Drupal\content_translation\Plugin\migrate\source\d6;

use Drupal\block\Plugin\migrate\source\Block;

/**
 * Gets i18n block data from source database.
 *
 * @MigrateSource(
 *   id = "d6_block_translation",
 *   source_module = "i18nblocks"
 * )
 */
class BlockTranslation extends Block {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Let the parent set the block table to use, but do not use the parent
    // query. Instead build a query so can use a left join to the selected block
    // table.
    parent::query();
    $query = $this->select('i18n_blocks', 'i18n')
      ->fields('i18n', ['ibid', 'module', 'delta', 'type', 'language'])
      ->fields('b', ['bid', 'module', 'delta', 'theme', 'title']);
    $query->addField('b', 'module', 'block_module');
    $query->addField('b', 'delta', 'block_delta');
    $query->leftJoin($this->blockTable, 'b', ('b.module = i18n.module AND b.delta = i18n.delta'));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['language'] = $this->t('Language for this field.');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = parent::getIds();
    $ids['module']['alias'] = 'b';
    $ids['delta']['alias'] = 'b';
    $ids['language']['type'] = 'string';
    return $ids;
  }

}
