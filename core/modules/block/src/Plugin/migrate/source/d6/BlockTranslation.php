<?php

namespace Drupal\block\Plugin\migrate\source\d6;

use Drupal\block\Plugin\migrate\source\Block;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;

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
    // query. Instead build a query so can use an inner join to the selected
    // block table.
    parent::query();
    $query = $this->select('i18n_blocks', 'i18n')
      ->fields('i18n')
      ->fields('b', ['bid', 'module', 'delta', 'theme', 'title']);
    $query->innerJoin($this->blockTable, 'b', ('b.module = i18n.module AND b.delta = i18n.delta'));
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'bid' => $this->t('The block numeric identifier.'),
      'ibid' => $this->t('The i18n_blocks block numeric identifier.'),
      'module' => $this->t('The module providing the block.'),
      'delta' => $this->t("The block's delta."),
      'type' => $this->t('Block type'),
      'language' => $this->t('Language for this field.'),
      'theme' => $this->t('Which theme the block is placed in.'),
      'default_theme' => $this->t('The default theme.'),
      'title' => $this->t('Block title.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('default_theme', $this->defaultTheme);
    return SourcePluginBase::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = parent::getIds();
    $ids['module']['alias'] = 'b';
    $ids['delta']['alias'] = 'b';
    $ids['theme']['alias'] = 'b';
    $ids['language']['type'] = 'string';
    return $ids;
  }

}
