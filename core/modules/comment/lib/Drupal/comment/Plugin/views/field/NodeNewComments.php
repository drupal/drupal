<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\NodeNewComments.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\Numeric;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to display the number of new comments.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("node_new_comments")
 */
class NodeNewComments extends Numeric {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['nid'] = 'nid';
    $this->additional_fields['type'] = 'type';
    $this->additional_fields['comment_count'] = array('table' => 'node_comment_statistics', 'field' => 'comment_count');
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_comment'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    $form['link_to_comment'] = array(
      '#title' => t('Link this field to new comments'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_comment'],
    );

    parent::buildOptionsForm($form, $form_state);
  }

  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
    $this->field_alias = $this->table . '_' . $this->field;
  }

  function pre_render(&$values) {
    global $user;
    if (!$user->uid || empty($values)) {
      return;
    }

    $nids = array();
    $ids = array();
    foreach ($values as $id => $result) {
      $nids[] = $result->{$this->aliases['nid']};
      $values[$id]->{$this->field_alias} = 0;
      // Create a reference so we can find this record in the values again.
      if (empty($ids[$result->{$this->aliases['nid']}])) {
        $ids[$result->{$this->aliases['nid']}] = array();
      }
      $ids[$result->{$this->aliases['nid']}][] = $id;
    }

    if ($nids) {
      $query = db_select('node', 'n');
      $query->addField('n', 'nid');
      $query->innerJoin('comment', 'c', 'n.nid = c.nid');
      $query->addExpression('COUNT(c.cid)', 'num_comments');
      $query->leftJoin('history', 'h', 'h.nid = n.nid');
      $query->condition('n.nid', $nids);
      $query->where('c.changed > GREATEST(COALESCE(h.timestamp, :timestamp), :timestamp)', array(':timestamp' => HISTORY_READ_LIMIT));
      $query->condition('c.status', COMMENT_PUBLISHED);
      $query->groupBy('n.nid');
      $result = $query->execute();
      foreach ($result as $node) {
        foreach ($ids[$node->nid] as $id) {
          $values[$id]->{$this->field_alias} = $node->num_comments;
        }
      }
    }
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_comment']) && $data !== NULL && $data !== '') {
      $node = entity_create('node', array(
        'nid' => $this->getValue($values, 'nid'),
        'type' => $this->getValue($values, 'type'),
      ));
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'node/' . $node->nid;
      $this->options['alter']['query'] = comment_new_page_count($this->getValue($values, 'comment_count'), $this->getValue($values), $node);
      $this->options['alter']['fragment'] = 'new';
    }

    return $data;
  }

  function render($values) {
    $value = $this->getValue($values);
    if (!empty($value)) {
      return $this->render_link(parent::render($values), $values);
    }
    else {
      $this->options['alter']['make_link'] = FALSE;
    }
  }

}
