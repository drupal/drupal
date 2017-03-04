<?php

namespace Drupal\history\Plugin\views\filter;

use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter for new content.
 *
 * The handler is named history_user, because of compatibility reasons, the
 * table is history.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("history_user_timestamp")
 */
class HistoryUserTimestamp extends FilterPluginBase {

  use UncacheableDependencyTrait;

  // Don't display empty space where the operator would be.
  public $no_operator = TRUE;

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    // @todo There are better ways of excluding required and multiple (object flags)
    unset($form['expose']['required']);
    unset($form['expose']['multiple']);
    unset($form['expose']['remember']);
  }

  protected function valueForm(&$form, FormStateInterface $form_state) {
    // Only present a checkbox for the exposed filter itself. There's no way
    // to tell the difference between not checked and the default value, so
    // specifying the default value via the views UI is meaningless.
    if ($form_state->get('exposed')) {
      if (isset($this->options['expose']['label'])) {
        $label = $this->options['expose']['label'];
      }
      else {
        $label = $this->t('Has new content');
      }
      $form['value'] = [
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => $this->value,
      ];
    }
  }

  public function query() {
    // This can only work if we're authenticated in.
    if (!\Drupal::currentUser()->isAuthenticated()) {
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
    $node = $this->query->ensureTable('node_field_data', $this->relationship);

    $clause = '';
    $clause2 = '';
    if ($ces = $this->query->ensureTable('comment_entity_statistics', $this->relationship)) {
      $clause = ("OR $ces.last_comment_timestamp > (***CURRENT_TIME*** - $limit)");
      $clause2 = "OR $field < $ces.last_comment_timestamp";
    }

    // NULL means a history record doesn't exist. That's clearly new content.
    // Unless it's very very old content. Everything in the query is already
    // type safe cause none of it is coming from outside here.
    $this->query->addWhereExpression($this->options['group'], "($field IS NULL AND ($node.changed > (***CURRENT_TIME*** - $limit) $clause)) OR $field < $node.changed $clause2");
  }

  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
  }

}
