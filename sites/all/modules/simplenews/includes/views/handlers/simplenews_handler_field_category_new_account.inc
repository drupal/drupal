<?php
// $Id: simplenews_handler_field_category_new_account.inc,v 1.1 2011/01/04 15:47:12 mirodietiker Exp $

/**
 * @file
 * Views field handler for simplenews_category.opt_inout.
 */

/**
 * Provide translatable simplenews_category.opt_inout Options.
 */
class simplenews_handler_field_category_new_account extends views_handler_field {
  function render($values) {
    $opt = array(
      'none' => t('None'),
      'on' => t('Default on'),
      'off' => t('Default off'),
      'silent' => t('invisible Subscrition'),
    );
    return check_plain($opt[$values->{$this->field_alias}]);
  }
}
