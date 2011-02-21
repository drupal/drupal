<?php
// $Id: views_plugin_access_none.inc,v 1.1 2008/09/08 22:50:17 merlinofchaos Exp $

/**
 * Access plugin that provides no access control at all.
 */
class views_plugin_access_none extends views_plugin_access {
  function summary_title() {
    return t('Unrestricted');
  }
}
