<?php
// $Id: views_handler_filter_node_language.inc,v 1.1.6.1 2011/01/06 00:37:05 dereine Exp $
/**
 * Filter by language
 */
class views_handler_filter_node_language extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Language');
      $languages = array(
        '***CURRENT_LANGUAGE***' => t("Current user's language"),
        '***DEFAULT_LANGUAGE***' => t("Default site language"),
        LANGUAGE_NONE => t('No language')
      );
      $languages = array_merge($languages, locale_language_list());
      $this->value_options = $languages;
    }
  }
}
