<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\HtmlTagUnitTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\UnitTestBase;

/**
 * Unit tests for theme_html_tag().
 */
class HtmlTagUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Theme HTML Tag',
      'description' => 'Tests theme_html_tag() built-in theme functions.',
      'group' => 'Theme',
    );
  }

  /**
   * Test function theme_html_tag()
   */
  function testThemeHtmlTag() {
    // Test auto-closure meta tag generation
    $tag['element'] = array('#tag' => 'meta', '#attributes' => array('name' => 'description', 'content' => 'Drupal test'));
    $this->assertEqual('<meta name="description" content="Drupal test" />'."\n", theme_html_tag($tag), t('Test auto-closure meta tag generation.'));

    // Test title tag generation
    $tag['element'] = array('#tag' => 'title', '#value' => 'title test');
    $this->assertEqual('<title>title test</title>'."\n", theme_html_tag($tag), t('Test title tag generation.'));
  }
}
