<?php

/**
 * @file
 * Definition of views_handler_field_comment_depth.
 */

/**
 * Field handler to display the depth of a comment.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_comment_depth extends views_handler_field {
  /**
   * Work out the depth of this comment
   */
  function render($values) {
    $comment_thread = $this->get_value($values);
    return count(explode('.', $comment_thread)) - 1;
  }
}
