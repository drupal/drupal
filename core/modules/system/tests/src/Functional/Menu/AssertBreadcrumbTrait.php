<?php

namespace Drupal\Tests\system\Functional\Menu;

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
   * @param \Drupal\Core\Url|string|null $goto
   *   (optional) A path or URL to pass to
   *   \Drupal\Tests\UiHelperTrait::drupalGet() otherwise a NULL value can be
   *   passed.
   * @param array $trail
   *   An associative array whose keys are expected breadcrumb link paths and
   *   whose values are expected breadcrumb link texts (not sanitized).
   * @param string $page_title
   *   (optional) A page title to additionally assert via
   *   \Drupal\Tests\WebAssert::titleEquals(). Without site name suffix.
   * @param array $tree
   *   (optional) An associative array whose keys are link paths and whose
   *   values are link titles (not sanitized) of an expected active trail in a
   *   menu tree output on the page.
   * @param $last_active
   *   (optional) Whether the last link in $tree is expected to be active (TRUE)
   *   or just to be in the active trail (FALSE).
   * @param string $active_trail_class
   *   (optional) The class of the active trail. Defaults to
   *   'menu-item--active-trail'.
   * @param string $active_class
   *   (optional) The class of the active element. Defaults to 'is-active'.
   */
  protected function assertBreadcrumb($goto, array $trail, $page_title = NULL, array $tree = [], $last_active = TRUE, $active_trail_class = 'menu-item--active-trail', $active_class = 'is-active') {
    if (isset($goto)) {
      $this->drupalGet($goto);
    }
    $this->assertBreadcrumbParts($trail);

    // Additionally assert page title, if given.
    if (isset($page_title)) {
      $this->assertSession()->titleEquals("$page_title | Drupal");
    }

    // Additionally assert active trail in a menu tree output, if given.
    if ($tree) {
      $this->assertMenuActiveTrail($tree, $last_active, $active_trail_class, $active_class);
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
    $found = $parts;
    $pass = TRUE;

    if (!empty($trail) && !empty($parts)) {
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
    elseif (!empty($trail) && empty($parts) || empty($trail) && !empty($parts)) {
      // Fail if there is no breadcrumb and we have a trail or breadcrumb
      // exists but trail is empty.
      $pass = FALSE;
    }

    // No parts must be left, or an expected "Home" will always pass.
    $pass = ($pass && empty($parts));

    $this->assertTrue($pass, sprintf('Expected breadcrumb %s on %s but found %s.',
      implode(' » ', $trail),
      $this->getUrl(),
      implode(' » ', array_map(function (array $item) {
        return $item['text'];
      }, $found)),
    ));
  }

  /**
   * Returns the breadcrumb contents of the current page in the internal browser.
   */
  protected function getBreadcrumbParts() {
    $parts = [];
    $elements = $this->xpath('//nav[@aria-labelledby="system-breadcrumb"]//ol/li/a');
    if (!empty($elements)) {
      foreach ($elements as $element) {
        $parts[] = [
          'text' => $element->getText(),
          'href' => $element->getAttribute('href'),
          'title' => $element->getAttribute('title'),
        ];
      }
    }
    return $parts;
  }

}
