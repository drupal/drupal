<?php

namespace Drupal\block\Plugin\migrate\source\d7;

use Drupal\block\Plugin\migrate\source\Block;

/**
 * Gets i18n block data from source database.
 *
 * For available configuration keys, refer to the parent classes:
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_block_translation",
 *   source_module = "i18n_block"
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
    $query = $this->select('i18n_string', 'i18n')
      ->fields('i18n')
      ->fields('b', [
        'bid',
        'module',
        'delta',
        'theme',
        'status',
        'weight',
        'region',
        'custom',
        'visibility',
        'pages',
        'title',
        'cache',
        'i18n_mode',
      ])
      ->fields('lt', [
        'lid',
        'translation',
        'language',
        'plid',
        'plural',
      ])
      ->condition('i18n_mode', 1);
    $query->leftjoin($this->blockTable, 'b', ('b.delta = i18n.objectid'));
    $query->innerJoin('locales_target', 'lt', 'lt.lid = i18n.lid');

    // The i18n_string module adds a status column to locale_target. It was
    // originally 'status' in a later revision it was named 'i18n_status'.
    /** @var \Drupal\Core\Database\Schema $db */
    if ($this->getDatabase()->schema()->fieldExists('locales_target', 'status')) {
      $query->addField('lt', 'status', 'i18n_status');
    }
    if ($this->getDatabase()->schema()->fieldExists('locales_target', 'i18n_status')) {
      $query->addField('lt', 'i18n_status', 'i18n_status');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'bid' => $this->t('The block numeric identifier.'),
      'module' => $this->t('The module providing the block.'),
      'delta' => $this->t("The block's delta."),
      'theme' => $this->t('Which theme the block is placed in.'),
      'status' => $this->t('Block enabled status'),
      'weight' => $this->t('Block weight within region'),
      'region' => $this->t('Theme region within which the block is set'),
      'visibility' => $this->t('Visibility'),
      'pages' => $this->t('Pages list.'),
      'title' => $this->t('Block title.'),
      'cache' => $this->t('Cache rule.'),
      'i18n_mode' => $this->t('Multilingual mode'),
      'lid' => $this->t('Language string ID'),
      'textgroup' => $this->t('A module defined group of translations'),
      'context' => $this->t('Full string ID for quick search: type:objectid:property.'),
      'objectid' => $this->t('Object ID'),
      'type' => $this->t('Object type for this string'),
      'property' => $this->t('Object property for this string'),
      'objectindex' => $this->t('Integer value of Object ID'),
      'format' => $this->t('The {filter_format}.format of the string'),
      'translation' => $this->t('Translation'),
      'language' => $this->t('Language code'),
      'plid' => $this->t('Parent lid'),
      'plural' => $this->t('Plural index number'),
      'i18n_status' => $this->t('Translation needs update'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['delta']['type'] = 'string';
    $ids['delta']['alias'] = 'b';
    $ids['language']['type'] = 'string';
    return $ids;
  }

}
