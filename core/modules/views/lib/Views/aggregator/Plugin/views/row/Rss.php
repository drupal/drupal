<?php

/**
 * @file
 * Definition of Views\aggregator\Plugin\views\row\Rss.
 */

namespace Views\aggregator\Plugin\views\row;

use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin which loads an aggregator item and formats it as an RSS item.
 *
 * @Plugin(
 *   id = "aggregator_rss",
 *   module = "aggregator",
 *   theme = "views_view_row_rss",
 *   title = @Translation("Aggregator item"),
 *   help = @Translation("Display the aggregator item using the data from the original source."),
 *   type = "feed"
 * )
 */
class Rss extends RowPluginBase {

  var $base_table = 'aggregator_item';
  var $base_field = 'iid';

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['item_length'] = array('default' => 'default');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['item_length'] = array(
      '#type' => 'select',
      '#title' => t('Display type'),
      '#options' => array(
        'fulltext' => t('Full text'),
        'teaser' => t('Title plus teaser'),
        'title' => t('Title only'),
        'default' => t('Use default RSS settings'),
      ),
      '#default_value' => $this->options['item_length'],
    );
  }

  function render($row) {
    $iid =  $row->{$this->field_alias};
    $query = db_select('aggregator_item', 'ai');
    $query->leftJoin('aggregator_feed', 'af', 'ai.fid = af.fid');
    $query->fields('ai');
    $query->addExpression('af.title', 'feed_title');
    $query->addExpression('ai.link', 'feed_LINK');
    $query->condition('iid', $iid);
    $result = $query->execute();

    $item->elements = array(
      array(
        'key' => 'pubDate',
        'value' => gmdate('r', $item->timestamp),
      ),
      array(
        'key' => 'dc:creator',
        'value' => $item->author,
      ),
      array(
        'key' => 'guid',
        'value' => $item->guid,
        'attributes' => array('isPermaLink' => 'false')
      ),
    );

    foreach ($item->elements as $element) {
      if (isset($element['namespace'])) {
        $this->view->style_plugin->namespaces = array_merge($this->view->style_plugin->namespaces, $element['namespace']);
      }
    }

    return theme($this->themeFunctions(), array(
      'view' => $this->view,
      'options' => $this->options,
      'row' => $item
    ));
  }

}
