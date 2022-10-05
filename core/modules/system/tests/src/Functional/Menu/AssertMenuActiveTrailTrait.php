<?php

namespace Drupal\Tests\system\Functional\Menu;

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
   * @param string $active_trail_class
   *   (optional) The class of the active trail. Defaults to
   *   'menu-item--active-trail'.
   * @param string $active_class
   *   (optional) The class of the active element. Defaults to 'is-active'.
   */
  protected function assertMenuActiveTrail($tree, $last_active, $active_trail_class = 'menu-item--active-trail', $active_class = 'is-active') {
    end($tree);
    $active_link_path = key($tree);
    $active_link_title = array_pop($tree);
    $xpath = '';
    if ($tree) {
      $i = 0;
      foreach ($tree as $link_path => $link_title) {
        $part_xpath = (!$i ? '//' : '/following-sibling::ul/descendant::');
        $part_xpath .= 'li[contains(@class, :class-trail)]/a[contains(@href, :href) and contains(text(), :title)]';
        $part_args = [
          ':class-trail' => $active_trail_class,
          ':href' => Url::fromUri('base:' . $link_path)->toString(),
          ':title' => $link_title,
        ];
        $xpath .= $this->assertSession()->buildXPathQuery($part_xpath, $part_args);
        $i++;
      }
      $this->assertSession()->elementExists('xpath', $xpath);

      // Append prefix for active link asserted below.
      $xpath .= '/following-sibling::ul/descendant::';
    }
    else {
      $xpath .= '//';
    }
    $xpath_last_active = ($last_active ? 'and contains(@class, :class-active)' : '');
    $xpath .= 'li[contains(@class, :class-trail)]/a[contains(@href, :href) ' . $xpath_last_active . 'and contains(text(), :title)]';
    $args = [
      ':class-trail' => $active_trail_class,
      ':class-active' => $active_class,
      ':href' => Url::fromUri('base:' . $active_link_path)->toString(),
      ':title' => $active_link_title,
    ];
    $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery($xpath, $args));
  }

}
