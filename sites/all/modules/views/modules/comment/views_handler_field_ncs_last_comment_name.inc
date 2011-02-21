<?php
// $Id: views_handler_field_ncs_last_comment_name.inc,v 1.1.6.1 2009/11/02 22:01:26 merlinofchaos Exp $

/**
 * Field handler to present the name of the last comment poster
 */
class views_handler_field_ncs_last_comment_name extends views_handler_field {
  function query() {
    // last_comment_name only contains data if the user is anonymous. So we
    // have to join in a specially related user table.
    $this->ensure_my_table();
    // join 'users' to this table via vid
    $join = new views_join();
    $join->construct('users', $this->table_alias, 'last_comment_uid', 'uid');
    $join->extra = array(array('field' => 'uid', 'operator' => '!=', 'value' => '0'));

    // ncs_user alias so this can work with the sort handler, below.
//    $this->user_table = $this->query->add_relationship(NULL, $join, 'users', $this->relationship);
    $this->user_table = $this->query->ensure_table('ncs_users', $this->relationship, $join);

    $this->field_alias = $this->query->add_field(NULL, "COALESCE($this->user_table.name, $this->table_alias.$this->field)", $this->table_alias . '_' . $this->field);

    $this->user_field = $this->query->add_field($this->user_table, 'name');
    $this->uid = $this->query->add_field($this->table_alias, 'last_comment_uid');
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['link_to_user'] = array('default' => TRUE);

    return $options;
  }

  function render($values) {
    if (!empty($this->options['link_to_user'])) {
      $account = new stdClass();
      $account->name = $values->{$this->field_alias};
      $account->uid = $values->{$this->uid};
      return theme('username', array(
        'account' => $account
      ));
    }
    else {
      return check_plain($values->{$this->field_alias});
    }
  }
}
