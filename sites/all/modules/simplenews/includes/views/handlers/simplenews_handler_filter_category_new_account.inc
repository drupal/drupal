<?php
// $Id: simplenews_handler_filter_category_new_account.inc,v 1.1 2011/01/04 15:47:12 mirodietiker Exp $

/**
 * @file
 * Views fiter handler for simplenews_category.new_account.
 */

/**
 * Provide translatable simplenews_category.new_account Options.
 */
class simplenews_handler_filter_category_new_account extends views_handler_filter_in_operator {
  function get_value_options() {
    $this->value_options = array(
      'none' => t('None'),
      'on' => t('Default on'),
      'off' => t('Default off'),
      'silent' => t('invisible Subscrition'),
    );
  }
}
