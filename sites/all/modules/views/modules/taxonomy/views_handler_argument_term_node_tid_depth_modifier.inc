<?php
// $Id: views_handler_argument_term_node_tid_depth_modifier.inc,v 1.1 2008/09/03 19:21:30 merlinofchaos Exp $

/**
 * Argument handler for to modify depth for a previous term.
 *
 * This handler is actually part of the node table and has some restrictions,
 * because it uses a subquery to find nodes with
 */
class views_handler_argument_term_node_tid_depth_modifier extends views_handler_argument {
  function options_form(&$form, &$form_state) { }
  function query() { }
  function pre_query() {
    // We don't know our argument yet, but it's based upon our position:
    $argument = isset($this->view->args[$this->position]) ? $this->view->args[$this->position] : NULL;
    if (!is_numeric($argument)) {
      return;
    }

    if ($argument > 10) {
      $argument = 10;
    }

    if ($argument < -10) {
      $argument = -10;
    }

    // figure out which argument preceded us.
    $keys = array_reverse(array_keys($this->view->argument));
    $skip = TRUE;
    foreach ($keys as $key) {
      if ($key == $this->options['id']) {
        $skip = FALSE;
        continue;
      }

      if ($skip) {
        continue;
      }

      if (empty($this->view->argument[$key])) {
        continue;
      }

      if (isset($handler)) {
        unset($handler);
      }

      $handler = &$this->view->argument[$key];
      if (empty($handler->definition['accept depth modifier'])) {
        continue;
      }

      // Finally!
      $handler->options['depth'] = $argument;
    }
  }
}

