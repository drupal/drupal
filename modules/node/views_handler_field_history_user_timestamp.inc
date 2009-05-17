<?php

/**
 * @file
 * Definition of views_handler_field_history_user_timestamp.
 */

/**
 * Field handler to display the marker for new content.
 *
 * The handler is named history_user, because of compability reasons, the table
 * is history.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_history_user_timestamp extends views_handler_field_node {
  function init(&$view, &$options) {
    parent::init($view, $options);
    global $user;
    if ($user->uid) {
      $this->additional_fields['created'] = array('table' => 'node', 'field' => 'created');
      $this->additional_fields['changed'] = array('table' => 'node', 'field' => 'changed');
      if (module_exists('comment') && !empty($this->options['comments'])) {
        $this->additional_fields['last_comment'] = array('table' => 'node_comment_statistics', 'field' => 'last_comment_timestamp');
      }
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['comments'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    if (module_exists('comment')) {
      $form['comments'] = array(
        '#type' => 'checkbox',
        '#title' => t('Check for new comments as well'),
        '#default_value' => !empty($this->options['comments']),
        '#fieldset' => 'more',
      );
    }
  }

  function query() {
    // Only add ourselves to the query if logged in.
    global $user;
    if (!$user->uid) {
      return;
    }
    parent::query();
  }

  function render($values) {
    // Let's default to 'read' state.
    // This code shadows node_mark, but it reads from the db directly and
    // we already have that info.
    $mark = MARK_READ;
    global $user;
    if ($user->uid) {
      $last_read = $this->get_value($values);
      $created = $this->get_value($values, 'created');
      $changed = $this->get_value($values, 'changed');

      $last_comment = module_exists('comment') && !empty($this->options['comments']) ?  $this->get_value($values, 'last_comment') : 0;

      if (!$last_read && $created > NODE_NEW_LIMIT) {
        $mark = MARK_NEW;
      }
      elseif ($changed > $last_read && $changed > NODE_NEW_LIMIT) {
        $mark = MARK_UPDATED;
      }
      elseif ($last_comment > $last_read && $last_comment > NODE_NEW_LIMIT) {
        $mark = MARK_UPDATED;
      }
      return $this->render_link(theme('mark', array('type' => $mark)), $values);
    }
  }
}
