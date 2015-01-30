<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\AssertMenuActiveTrailTrait.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Core\Url;

/**
 * Provides test assertions for verifying the active menu trail.
 */
trait AssertMenuActiveTrailTrait {

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
          ':href' => Url::fromUri('base:' . $link_path)->toString(),
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
      ':href' => Url::fromUri('base:' . $active_link_path)->toString(),
      ':title' => $active_link_title,
    );
    $elements = $this->xpath($xpath, $args);
    $this->assertTrue(!empty($elements), format_string('Active link %title was found in menu tree, including active trail links %tree.', array(
      '%title' => $active_link_title,
      '%tree' => implode(' » ', $tree),
    )));
  }

}
