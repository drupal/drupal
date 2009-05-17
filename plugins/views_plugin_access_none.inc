<?php

/**
 * @file
 * Definition of views_plugin_access_none.
 */

/**
 * Access plugin that provides no access control at all.
 *
 * @ingroup views_access_plugins
 */
class views_plugin_access_none extends views_plugin_access {
  function summary_title() {
    return t('Unrestricted');
  }
}
