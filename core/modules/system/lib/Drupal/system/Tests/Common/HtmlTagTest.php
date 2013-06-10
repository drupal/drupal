<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\HtmlTagTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for #type 'html_tag'.
 */
class HtmlTagTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Render HTML tags',
      'description' => 'Tests rendering of html_tag type renderable arrays.',
      'group' => 'Common',
    );
  }

  /**
   * Tests #type 'html_tag'.
   */
  function testHtmlTag() {
    // Test auto-closure meta tag generation.
    $tag = array(
      '#type' => 'html_tag',
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'description',
        'content' => 'Drupal test',
      ),
    );
    $this->assertEqual('<meta name="description" content="Drupal test" />' . "\n", drupal_render($tag), 'Test auto-closure meta tag generation.');

    // Test title tag generation.
    $tag = array(
      '#type' => 'html_tag',
      '#tag' => 'title',
      '#value' => 'title test',
    );
    $this->assertEqual('<title>title test</title>' . "\n", drupal_render($tag), 'Test title tag generation.');
  }
}
