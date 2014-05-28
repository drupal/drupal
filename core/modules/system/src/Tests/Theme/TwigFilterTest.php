<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigFilterTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Template\Attribute;

/**
 * Tests Drupal's Twig filters.
 */
class TwigFilterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('twig_theme_test');

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
    $filter_test = array(
      '#theme' => 'twig_theme_test_filter',
      '#quote' => array(
        'content' => array('#markup' => 'You can only find truth with logic if you have already found truth without it.'),
        'author' => array('#markup' => 'Gilbert Keith Chesterton'),
        'date' => array('#markup' => '1874-1936'),
      ),
      '#attributes' => array(
        'id' => 'quotes',
        'checked' => TRUE,
        'class' => array('red', 'green', 'blue'),
      ),
    );
    $rendered = drupal_render($filter_test);
    $this->drupalSetContent($rendered);

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
      array(
        'expected' => '<div><span id="quotes" checked class="red green blue">All attributes:</span></div>',
        'message' => 'All attributes printed.',
      ),
      array(
        'expected' => '<div><span class="red green blue" id="quotes" checked>Class attributes in front, remainder at the back:</span></div>',
        'message' => 'Class attributes printed in the front, the rest in the back.',
      ),
      array(
        'expected' => '<div><span id="quotes" checked data-class="red green blue">Class attributes in back, remainder at the front:</span></div>',
        'message' => 'Class attributes printed in the back, the rest in the front.',
      ),
      array(
        'expected' => '<div><span class="red green blue">Class attributes only:</span></div>',
        'message' => 'Class attributes only printed.',
      ),
      array(
        'expected' => '<div><span checked id="quotes" class="red green blue">Without boolean attribute.</span></div>',
        'message' => 'Boolean attribute printed in the front.',
      ),
      array(
        'expected' => '<div><span data-id="quotes" checked class="red green blue">Without string attribute.</span></div>',
        'message' => 'Without string attribute in the front.',
      ),
      array(
        'expected' => '<div><span checked>Without either nor class attributes.</span></div>',
        'message' => 'Attributes printed without id nor class attributes.',
      ),
      array(
        'expected' => '<div><span id="quotes" checked class="red green blue">All attributes again.</span></div>',
        'message' => 'All attributes printed again.',
      ),
    );

    foreach ($elements as $element) {
      $this->assertRaw($element['expected'], $element['message']);
    }
  }

}
