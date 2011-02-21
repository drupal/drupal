<?php
// $Id: simplenews_handler_filter_category_hyperlinks.inc,v 1.1 2011/01/04 15:47:12 mirodietiker Exp $

/**
 * @file
 * Views handler for simplenews field simplewnews_category.hyperlinks.
 */

/**
 * Display HTML Mail Hyperlinks position settings.
 */
class simplenews_handler_filter_category_hyperlinks extends views_handler_filter_in_operator {
  function get_value_options() {
    $this->value_options = array(
      0 => t('Bottom'),
      1 => t('Inline'),
    );
  }
}
