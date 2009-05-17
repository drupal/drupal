<?php

/**
 * @file
 * Definition of views_handler_field_node_new_comments.
 */

/**
 * Field handler to display the number of new comments.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_node_new_comments extends views_handler_field_numeric {
  function init(&$view, &$options) {
    parent::init($view, $options);

    // translate an older setting:
    if (!empty($options['no_empty'])) {
      $this->options['hide_empty'] = TRUE;
      unset($this->options['no_empty']);
    }
  }

  function construct() {
    parent::construct();
    $this->additional_fields['nid'] = 'nid';
    $this->additional_fields['type'] = 'type';
    $this->additional_fields['comment_count'] = array('table' => 'node_comment_statistics', 'field' => 'comment_count');
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['link_to_comment'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['link_to_comment'] = array(
      '#title' => t('Link this field to new comments'),
      '#description' => t("Enable to override this field's links."),
      '#type' => 'checkbox',
      '#default_value' => $this->options['link_to_comment'],
    );

    parent::options_form($form, $form_state);
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
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
      $result = db_query("SELECT n.nid, COUNT(c.cid) as num_comments FROM {node} n INNER JOIN {comment} c ON n.nid = c.nid
        LEFT JOIN {history} h ON h.nid = n.nid AND h.uid = :h_uid WHERE n.nid IN (:nids)
        AND c.changed > GREATEST(COALESCE(h.timestamp, :timestamp), :timestamp) AND c.status = :status GROUP BY n.nid  ", array(
          ':status' => COMMENT_PUBLISHED,
          ':h_uid' => $user->uid,
          ':nids' => $nids,
          ':timestamp' => NODE_NEW_LIMIT,
        ));

      foreach ($result as $node) {
        foreach ($ids[$node->nid] as $id) {
          $values[$id]->{$this->field_alias} = $node->num_comments;
        }
      }
    }
  }

  function render_link($data, $values) {
    if (!empty($this->options['link_to_comment']) && $data !== NULL && $data !== '') {
      $node = new stdClass();
      $node->nid = $this->get_value($values, 'nid');
      $node->type = $this->get_value($values, 'type');
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'node/' . $node->nid;
      $this->options['alter']['query'] = comment_new_page_count($this->get_value($values, 'comment_count'), $this->get_value($values), $node);
      $this->options['alter']['fragment'] = 'new';
    }

    return $data;
  }

  function render($values) {
    $value = $this->get_value($values);
    if (!empty($value)) {
      return $this->render_link(parent::render($values), $values);
    }
    else {
      $this->options['alter']['make_link'] = FALSE;
    }
  }
}
