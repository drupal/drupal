<?php

/**
 * @file
 * Contains \Drupal\history\Plugin\views\filter\HistoryUserTimestamp.
 */

namespace Drupal\history\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter for new content.
 *
 * The handler is named history_user, because of compatibility reasons, the
 * table is history.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("history_user_timestamp")
 */
class HistoryUserTimestamp extends FilterPluginBase {

  // Don't display empty space where the operator would be.
  var $no_operator = TRUE;

  public function buildExposeForm(&$form, &$form_state) {
    parent::buildExposeForm($form, $form_state);
    // @todo There are better ways of excluding required and multiple (object flags)
    unset($form['expose']['required']);
    unset($form['expose']['multiple']);
    unset($form['expose']['remember']);
  }

  protected function valueForm(&$form, &$form_state) {
    // Only present a checkbox for the exposed filter itself. There's no way
    // to tell the difference between not checked and the default value, so
    // specifying the default value via the views UI is meaningless.
    if (!empty($form_state['exposed'])) {
      if (isset($this->options['expose']['label'])) {
        $label = $this->options['expose']['label'];
      }
      else {
        $label = t('Has new content');
      }
      $form['value'] = array(
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => $this->value,
      );
    }
  }

  public function query() {
    global $user;
    // This can only work if we're logged in.
    if (!$user || !$user->uid) {
      return;
    }

    // Don't filter if we're exposed and the checkbox isn't selected.
    if ((!empty($this->options['exposed'])) && empty($this->value)) {
      return;
    }

    // Hey, Drupal kills old history, so nodes that haven't been updated
    // since HISTORY_READ_LIMIT are bzzzzzzzt outta here!

    $limit = REQUEST_TIME - HISTORY_READ_LIMIT;

    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    $node = $this->query->ensure_table('node_field_data', $this->relationship);

    $clause = '';
    $clause2 = '';
    if (module_exists('comment')) {
      $ncs = $this->query->ensure_table('node_comment_statistics', $this->relationship);
      $clause = ("OR $ncs.last_comment_timestamp > (***CURRENT_TIME*** - $limit)");
      $clause2 = "OR $field < $ncs.last_comment_timestamp";
    }

    // NULL means a history record doesn't exist. That's clearly new content.
    // Unless it's very very old content. Everything in the query is already
    // type safe cause none of it is coming from outside here.
    $this->query->add_where_expression($this->options['group'], "($field IS NULL AND ($node.changed > (***CURRENT_TIME*** - $limit) $clause)) OR $field < $node.changed $clause2");
  }

  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return t('exposed');
    }
  }

}
