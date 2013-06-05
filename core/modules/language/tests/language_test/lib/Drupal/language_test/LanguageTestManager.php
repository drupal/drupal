<?php

/**
 * @file
 * Contains \Drupal\language_test\LanguageTestManager.
 */

namespace Drupal\language_test;

use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a LanguageManager service to test URL negotiation.
 */
class LanguageTestManager extends LanguageManager {

  /**
   * Overrides \Drupal\Core\Language\LanguageManager::init().
   */
  public function init() {
    if ($test_domain = \Drupal::state()->get('language_test.domain')) {
      $_SERVER['HTTP_HOST'] = $test_domain;
    }
    return parent::init();
  }

}
