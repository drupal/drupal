<?php

/**
 * @file
 * Contains \Drupal\language\LanguageSwitcherInterface.
 */

namespace Drupal\language;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface for language switcher classes.
 */
interface LanguageSwitcherInterface {

  /**
   * Returns language switch links.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $type
   *   The language type.
   * @param \Drupal\Core\Url $url
   *   The URL the switch links will be relative to.
   *
   * @return array
   *   An array of link arrays keyed by language code.
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url);

}
