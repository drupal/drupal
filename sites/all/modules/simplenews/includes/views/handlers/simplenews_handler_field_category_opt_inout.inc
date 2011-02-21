<?php
// $Id: simplenews_handler_field_category_opt_inout.inc,v 1.1 2011/01/04 15:47:12 mirodietiker Exp $

/**
 * @file
 * Views field handler for simplenews_category.opt_inout.
 */

/**
 * Provide translatable simplenews_category.opt_inout Options.
 */
class simplenews_handler_field_category_opt_inout extends views_handler_field {
  function render($values) {
    $opt = array(
      'hidden' => t('Hidden'),
      'single' => t('Single opt-in'),
      'double' => t('Double opt-in'),
    );
    return check_plain($opt[$values->{$this->field_alias}]);
  }
}
