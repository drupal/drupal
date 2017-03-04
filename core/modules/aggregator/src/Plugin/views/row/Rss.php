<?php

namespace Drupal\aggregator\Plugin\views\row;

use Drupal\views\Plugin\views\row\RssPluginBase;

/**
 * Defines a row plugin which loads an aggregator item and renders as RSS.
 *
 * @ViewsRow(
 *   id = "aggregator_rss",
 *   theme = "views_view_row_rss",
 *   title = @Translation("Aggregator item"),
 *   help = @Translation("Display the aggregator item using the data from the original source."),
 *   base = {"aggregator_item"},
 *   display_types = {"feed"}
 * )
 */
class Rss extends RssPluginBase {

  /**
   * The table the aggregator item is using for storage.
   *
   * @var string
   */
  public $base_table = 'aggregator_item';

  /**
   * The actual field which is used to identify a aggregator item.
   *
   * @var string
   */
  public $base_field = 'iid';

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'aggregator_item';

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $entity = $row->_entity;

    $item = new \stdClass();
    foreach ($entity as $name => $field) {
      $item->{$name} = $field->value;
    }

    $item->elements = [
      [
        'key' => 'pubDate',
        // views_view_row_rss takes care about the escaping.
        'value' => gmdate('r', $entity->timestamp->value),
      ],
      [
        'key' => 'dc:creator',
        // views_view_row_rss takes care about the escaping.
        'value' => $entity->author->value,
      ],
      [
        'key' => 'guid',
        // views_view_row_rss takes care about the escaping.
        'value' => $entity->guid->value,
        'attributes' => ['isPermaLink' => 'false'],
      ],
    ];

    $build = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    ];
    return $build;
  }

}
