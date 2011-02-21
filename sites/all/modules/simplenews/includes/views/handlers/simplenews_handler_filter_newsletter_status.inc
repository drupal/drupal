<?php
// $Id: simplenews_handler_filter_newsletter_status.inc,v 1.2 2011/01/04 15:47:12 mirodietiker Exp $

/**
 * @file
 * Views filter for simplenews sent status.
 */

/**
 * Filter based on newsletter sent status.
 */
class simplenews_handler_filter_newsletter_status extends views_handler_filter_in_operator {
  function get_value_options() {
    $this->value_options = array(
      SIMPLENEWS_STATUS_SEND_NOT => t('Not sent'),
      SIMPLENEWS_STATUS_SEND_PENDING => t('Pending'),
      SIMPLENEWS_STATUS_SEND_READY => t('Sent'),
    );
  }
}
