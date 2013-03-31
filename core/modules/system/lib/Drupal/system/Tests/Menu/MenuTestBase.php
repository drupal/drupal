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
    // Compare paths with actual breadcrumb.
    $parts = $this->getParts();
    $pass = TRUE;
    foreach ($trail as $path => $title) {
      $url = url($path);
      $part = array_shift($parts);
      $pass = ($pass && $part['href'] === $url && $part['text'] === check_plain($title));
    }
    // No parts must be left, or an expected "Home" will always pass.
    $pass = ($pass && empty($parts));

    $this->assertTrue($pass, format_string('Breadcrumb %parts found on @path.', array(
      '%parts' => implode(' » ', $trail),
      '@path' => $this->getUrl(),
    )));

    // Additionally assert page title, if given.
    if (isset($page_title)) {
      $this->assertTitle(strtr('@title | Drupal', array('@title' => $page_title)));
    }

    // Additionally assert active trail in a menu tree output, if given.
    if ($tree) {
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
  }

  /**
   * Returns the breadcrumb contents of the current page in the internal browser.
   */
  protected function getParts() {
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

  /**
   * Asserts local tasks in the page output.
   *
   * @param array $hrefs
   *   A list of expected link hrefs of local tasks to assert on the page (in
   *   the given order).
   * @param int $level
   *   (optional) The local tasks level to assert; 0 for primary, 1 for
   *   secondary. Defaults to 0.
   */
  protected function assertLocalTasks(array $hrefs, $level = 0) {
    $elements = $this->xpath('//*[contains(@class, :class)]//a', array(
      ':class' => $level == 0 ? 'tabs primary' : 'tabs secondary',
    ));
    $this->assertTrue(count($elements), 'Local tasks found.');
    foreach ($hrefs as $index => $element) {
      $expected = url($hrefs[$index]);
      $method = ($elements[$index]['href'] == $expected ? 'pass' : 'fail');
      $this->{$method}(format_string('Task @number href @value equals @expected.', array(
        '@number' => $index + 1,
        '@value' => (string) $elements[$index]['href'],
        '@expected' => $expected,
      )));
    }
  }

}
