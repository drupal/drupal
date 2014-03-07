<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigFilterTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Drupal's Twig filters.
 */
class TwigFilterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'twig_theme_test',
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Twig Filters',
      'description' => 'Test Drupal\'s Twig filters.',
      'group' => 'Theme',
    );
  }

  /**
   * Test Twig "without" filter.
   */
  public function testTwigWithoutFilter() {
    $this->drupalGet('/twig-theme-test/filter');

    $elements = array(
      array(
        'expected' => '<div><strong>No author:</strong> You can only find truth with logic if you have already found truth without it.1874-1936.</div>',
        'message' => '"No author" was successfully rendered.',
      ),
      array(
        'expected' => '<div><strong>Complete quote after without:</strong> You can only find truth with logic if you have already found truth without it.Gilbert Keith Chesterton1874-1936.</div>',
        'message' => '"Complete quote after without" was successfully rendered.',
      ),
      array(
        'expected' => '<div><strong>Only author:</strong> Gilbert Keith Chesterton.</div>',
        'message' => '"Only author:" was successfully rendered.',
      ),
      array(
        'expected' => '<div><strong>No author or date:</strong> You can only find truth with logic if you have already found truth without it..</div>',
        'message' => '"No author or date" was successfully rendered.',
      ),
      array(
        'expected' => '<div><strong>Only date:</strong> 1874-1936.</div>',
        'message' => '"Only date" was successfully rendered.',
      ),
      array(
        'expected' => '<div><strong>Complete quote again for good measure:</strong> You can only find truth with logic if you have already found truth without it.Gilbert Keith Chesterton1874-1936.</div>',
        'message' => '"Complete quote again for good measure" was successfully rendered.',
      ),
      array(
        'expected' => '<div><strong>Marked-up:</strong>
  <blockquote>
    <p>You can only find truth with logic if you have already found truth without it.</p>
    <footer>
      &ndash; <cite><a href="#">Gilbert Keith Chesterton</a> <em>(1874-1936)</em></cite>
    </footer>
  </blockquote>',
        'message' => '"Marked-up quote" was successfully rendered.',
      ),
    );

    foreach ($elements as $element) {
      $this->assertRaw($element['expected'], $element['message']);
    }
  }

}
