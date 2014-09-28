<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\views\row\Rss.
 */

namespace Drupal\aggregator\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\RowPluginBase;

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
class Rss extends RowPluginBase {

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
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = array('default' => 'default');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['view_mode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display type'),
      '#options' => array(
        'fulltext' => $this->t('Full text'),
        'teaser' => $this->t('Title plus teaser'),
        'title' => $this->t('Title only'),
        'default' => $this->t('Use default RSS settings'),
      ),
      '#default_value' => $this->options['view_mode'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $entity = $row->_entity;

    $item = new \stdClass();
    foreach ($entity as $name => $field) {
      // views_view_row_rss takes care about the escaping.
      $item->{$name} = $field->value;
    }

    $item->elements = array(
      array(
        'key' => 'pubDate',
        // views_view_row_rss takes care about the escaping.
        'value' => gmdate('r', $entity->timestamp->value),
      ),
      array(
        'key' => 'dc:creator',
        // views_view_row_rss takes care about the escaping.
        'value' => $entity->author->value,
      ),
      array(
        'key' => 'guid',
        // views_view_row_rss takes care about the escaping.
        'value' => $entity->guid->value,
        'attributes' => array('isPermaLink' => 'false'),
      ),
    );

    $build = array(
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->options,
      '#row' => $item,
    );
    return drupal_render($build);
  }

}
