<?php

/**
 * @file
 * Contains \Drupal\language\Controller\LanguageController.
 */

namespace Drupal\language\Controller;

/**
 * Returns responses for language routes.
 */
class LanguageController {

  /**
   * @todo Remove language_content_settings_page().
   */
  public function contentSettings() {
    module_load_include('admin.inc', 'language');
    return language_content_settings_page();
  }

}
