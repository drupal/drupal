<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\NcsLastCommentName.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Field handler to present the name of the last comment poster.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("comment_ncs_last_comment_name")
 */
class NcsLastCommentName extends FieldPluginBase {

  public function query() {
    // last_comment_name only contains data if the user is anonymous. So we
    // have to join in a specially related user table.
    $this->ensureMyTable();
    // join 'users' to this table via vid
    $definition = array(
      'table' => 'users',
      'field' => 'uid',
      'left_table' => $this->tableAlias,
      'left_field' => 'last_comment_uid',
      'extra' => array(
        array(
          'field' => 'uid',
          'operator' => '!=',
          'value' => '0'
        )
      )
    );
    $join = drupal_container()->get('plugin.manager.views.join')->createInstance('standard', $definition);

    // ncs_user alias so this can work with the sort handler, below.
    $this->user_table = $this->query->ensureTable('ncs_users', $this->relationship, $join);

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
      $account = entity_create('user', array());
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
