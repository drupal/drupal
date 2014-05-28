<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\views\field\StatisticsLastCommentName.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present the name of the last comment poster.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("comment_ces_last_comment_name")
 */
class StatisticsLastCommentName extends FieldPluginBase {

  public function query() {
    // last_comment_name only contains data if the user is anonymous. So we
    // have to join in a specially related user table.
    $this->ensureMyTable();
    // join 'users' to this table via vid
    $definition = array(
      'table' => 'users',
      'field' => 'uid',
      'left_table' => 'comment_entity_statistics',
      'left_field' => 'last_comment_uid',
      'extra' => array(
        array(
          'field' => 'uid',
          'operator' => '!=',
          'value' => '0'
        )
      )
    );
    $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $definition);

    // nes_user alias so this can work with the sort handler, below.
    $this->user_table = $this->query->ensureTable('ces_users', $this->relationship, $join);

    $this->field_alias = $this->query->addField(NULL, "COALESCE($this->user_table.name, $this->tableAlias.$this->field)", $this->tableAlias . '_' . $this->field);

    $this->user_field = $this->query->addField($this->user_table, 'name');
    $this->uid = $this->query->addField($this->tableAlias, 'last_comment_uid');
  }

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['link_to_user'] = array('default' => TRUE, 'bool' => TRUE);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (!empty($this->options['link_to_user'])) {
      $account = entity_create('user');
      $account->name = $this->getValue($values);
      $account->uid = $values->{$this->uid};
      $username = array(
        '#theme' => 'username',
        '#account' => $account,
      );
      return drupal_render($username);
    }
    else {
      return $this->sanitizeValue($this->getValue($values));
    }
  }

}
