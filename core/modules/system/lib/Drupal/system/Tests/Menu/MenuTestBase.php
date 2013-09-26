<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\MenuTestBase.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\WebTestBase;

abstract class MenuTestBase extends WebTestBase {

  /**
   * Assert that a given path shows certain breadcrumb links.
   *
   * @param string $goto
   *   (optional) A system path to pass to
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
        $url = url($path);
        $part = array_shift($parts);
        $pass = ($pass && $part['href'] === $url && $part['text'] === check_plain($title));
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
   * Assert that active trail exists in a menu tree output.
   *
   * @param array $tree
   *   An associative array whose keys are link paths and whose
   *   values are link titles (not sanitized) of an expected active trail in a
   *   menu tree output on the page.
   * @param bool $last_active
   *   Whether the last link in $tree is expected to be active (TRUE)
   *   or just to be in the active trail (FALSE).
   */
  protected function assertMenuActiveTrail($tree, $last_active) {
    end($tree);
    $active_link_path = key($tree);
    $active_link_title = array_pop($tree);
    $xpath = '';
    if ($tree) {
      $i = 0;
      foreach ($tree as $link_path => $link_title) {
        $part_xpath = (!$i ? '//' : '/following-sibling::ul/descendant::');
        $part_xpath .= 'li[contains(@class, :class)]/a[contains(@href, :href) and contains(text(), :title)]';
        $part_args = array(
          ':class' => 'active-trail',
          ':href' => url($link_path),
          ':title' => $link_title,
        );
        $xpath .= $this->buildXPathQuery($part_xpath, $part_args);
        $i++;
      }
      $elements = $this->xpath($xpath);
      $this->assertTrue(!empty($elements), 'Active trail to current page was found in menu tree.');

      // Append prefix for active link asserted below.
      $xpath .= '/following-sibling::ul/descendant::';
    }
    else {
      $xpath .= '//';
    }
    $xpath_last_active = ($last_active ? 'and contains(@class, :class-active)' : '');
    $xpath .= 'li[contains(@class, :class-trail)]/a[contains(@href, :href) ' . $xpath_last_active . 'and contains(text(), :title)]';
    $args = array(
      ':class-trail' => 'active-trail',
      ':class-active' => 'active',
      ':href' => url($active_link_path),
      ':title' => $active_link_title,
    );
    $elements = $this->xpath($xpath, $args);
    $this->assertTrue(!empty($elements), format_string('Active link %title was found in menu tree, including active trail links %tree.', array(
      '%title' => $active_link_title,
      '%tree' => implode(' » ', $tree),
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
