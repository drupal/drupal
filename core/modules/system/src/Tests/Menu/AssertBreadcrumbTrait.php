<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\AssertBreadcrumbTrait.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Provides test assertions for verifying breadcrumbs.
 */
trait AssertBreadcrumbTrait {

  use AssertMenuActiveTrailTrait;

  /**
   * Assert that a given path shows certain breadcrumb links.
   *
   * @param \Drupal\Core\Url|string $goto
   *   (optional) A path or URL to pass to
   *   Drupal\simpletest\WebTestBase::drupalGet().
   * @param array $trail
   *   An associative array whose keys are expected breadcrumb link paths and
   *   whose values are expected breadcrumb link texts (not sanitized).
   * @param string $page_title
   *   (optional) A page title to additionally assert via
   *   Drupal\simpletest\WebTestBase::assertTitle(). Without site name suffix.
   * @param array $tree
   *   (optional) An associative array whose keys are link paths and whose
   *   values are link titles (not sanitized) of an expected active trail in a
   *   menu tree output on the page.
   * @param $last_active
   *   (optional) Whether the last link in $tree is expected to be active (TRUE)
   *   or just to be in the active trail (FALSE).
   */
  protected function assertBreadcrumb($goto, array $trail, $page_title = NULL, array $tree = array(), $last_active = TRUE) {
    if (isset($goto)) {
      $this->drupalGet($goto);
    }
    $this->assertBreadcrumbParts($trail);

    // Additionally assert page title, if given.
    if (isset($page_title)) {
      $this->assertTitle(strtr('@title | Drupal', array('@title' => $page_title)));
    }

    // Additionally assert active trail in a menu tree output, if given.
    if ($tree) {
      $this->assertMenuActiveTrail($tree, $last_active);
    }
  }

  /**
   * Assert that a trail exists in the internal browser.
   *
   * @param array $trail
   *   An associative array whose keys are expected breadcrumb link paths and
   *   whose values are expected breadcrumb link texts (not sanitized).
   */
  protected function assertBreadcrumbParts($trail) {
    // Compare paths with actual breadcrumb.
    $parts = $this->getBreadcrumbParts();
    $pass = TRUE;
    // There may be more than one breadcrumb on the page. If $trail is empty
    // this test would go into an infinite loop, so we need to check that too.
    while ($trail && !empty($parts)) {
      foreach ($trail as $path => $title) {
        // If the path is empty, generate the path from the <front> route.  If
        // the path does not start with a leading slash, then run it through
        // Url::fromUri('base:')->toString() to get the correct base
        // prepended.
        if ($path == '') {
          $url = Url::fromRoute('<front>')->toString();
        }
        elseif ($path[0] != '/') {
          $url = Url::fromUri('base:' . $path)->toString();
        }
        else {
          $url = $path;
        }
        $part = array_shift($parts);
        $pass = ($pass && $part['href'] === $url && $part['text'] === Html::escape($title));
      }
    }
    // No parts must be left, or an expected "Home" will always pass.
    $pass = ($pass && empty($parts));

    $this->assertTrue($pass, format_string('Breadcrumb %parts found on @path.', array(
      '%parts' => implode(' » ', $trail),
      '@path' => $this->getUrl(),
    )));
  }

  /**
   * Returns the breadcrumb contents of the current page in the internal browser.
   */
  protected function getBreadcrumbParts() {
    $parts = array();
    $elements = $this->xpath('//nav[@class="breadcrumb"]/ol/li/a');
    if (!empty($elements)) {
      foreach ($elements as $element) {
        $parts[] = array(
          'text' => (string) $element,
          'href' => (string) $element['href'],
          'title' => (string) $element['title'],
        );
      }
    }
    return $parts;
  }

}
