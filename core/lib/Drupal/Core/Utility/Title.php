<?php

/**
 * @file
 * Contains \Drupal\Core\Utility\Title.
 */

namespace Drupal\Core\Utility;

/**
 * Defines some constants related with Drupal page title.
 */
class Title {

  /**
   * Flag for controller titles, for sanitizing via String::checkPlain
   */
  const CHECK_PLAIN = 0;

  /**
   * For controller titles, for sanitizing via Xss::filterAdmin.
   */
  const FILTER_XSS_ADMIN = 1;

  /**
   * For controller titles, text has already been sanitized.
   */
  const PASS_THROUGH = -1;

}
