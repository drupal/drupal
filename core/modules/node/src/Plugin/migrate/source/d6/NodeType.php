<?php

namespace Drupal\node\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 Node types source from database.
 *
 * @MigrateSource(
 *   id = "d6_node_type",
 *   source_module = "node"
 * )
 */
class NodeType extends DrupalSqlBase {

  /**
   * The teaser length
   *
   * @var int
   */
  protected $teaserLength;

  /**
   * Node preview optional / required.
   *
   * @var int
   */
  protected $nodePreview;

  /**
   * An array of theme settings.
   *
   * @var array
   */
  protected $themeSettings;

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('node_type', 't')
      ->fields('t', [
        'type',
        'name',
        'module',
        'description',
        'help',
        'title_label',
        'has_body',
        'body_label',
        'min_word_count',
        'custom',
        'modified',
        'locked',
        'orig_type',
      ])
      ->orderBy('t.type');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Machine name of the node type.'),
      'name' => $this->t('Human name of the node type.'),
      'module' => $this->t('The module providing the node type.'),
      'description' => $this->t('Description of the node type.'),
      'help' => $this->t('Help text for the node type.'),
      'title_label' => $this->t('Title label.'),
      'has_body' => $this->t('Flag indicating the node type has a body field.'),
      'body_label' => $this->t('Body label.'),
      'min_word_count' => $this->t('Minimum word count for the body field.'),
      'custom' => $this->t('Flag.'),
      'modified' => $this->t('Flag.'),
      'locked' => $this->t('Flag.'),
      'orig_type' => $this->t('The original type.'),
      'teaser_length' => $this->t('Teaser length'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $this->teaserLength = $this->variableGet('teaser_length', 600);
    $this->nodePreview = $this->variableGet('node_preview', 0);
    $this->themeSettings = $this->variableGet('theme_settings', []);
    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('teaser_length', $this->teaserLength);
    $row->setSourceProperty('node_preview', $this->nodePreview);

    $type = $row->getSourceProperty('type');
    $source_options = $this->variableGet('node_options_' . $type, ['promote', 'sticky']);
    $options = [];
    foreach (['promote', 'sticky', 'status', 'revision'] as $item) {
      $options[$item] = in_array($item, $source_options);
    }
    $row->setSourceProperty('options', $options);
    $submitted = isset($this->themeSettings['toggle_node_info_' . $type]) ? $this->themeSettings['toggle_node_info_' . $type] : FALSE;
    $row->setSourceProperty('display_submitted', $submitted);

    if ($default_node_menu = $this->variableGet('menu_default_node_menu', NULL)) {
      $row->setSourceProperty('available_menus', [$default_node_menu]);
      $row->setSourceProperty('parent', $default_node_menu . ':');
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    return $ids;
  }

}
