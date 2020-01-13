<?php

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\Core\Extension\Extension;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Drupal's Twig filters.
 *
 * @group Theme
 */
class TwigFilterTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['twig_theme_test'];

  /**
   * Test Twig "without" filter.
   */
  public function testTwigWithoutFilter() {
    $filter_test = [
      '#theme' => 'twig_theme_test_filter',
      '#quote' => [
        'content' => ['#markup' => 'You can only find truth with logic if you have already found truth without it.'],
        'author' => ['#markup' => 'Gilbert Keith Chesterton'],
        'date' => ['#markup' => '1874-1936'],
      ],
      '#attributes' => [
        'id' => 'quotes',
        'checked' => TRUE,
        'class' => ['red', 'green', 'blue'],
      ],
    ];
    $rendered = \Drupal::service('renderer')->renderRoot($filter_test);
    $this->setRawContent($rendered);

    $elements = [
      [
        'expected' => '<div><strong>No author:</strong> You can only find truth with logic if you have already found truth without it.1874-1936.</div>',
        'message' => '"No author" was successfully rendered.',
      ],
      [
        'expected' => '<div><strong>Complete quote after without:</strong> You can only find truth with logic if you have already found truth without it.Gilbert Keith Chesterton1874-1936.</div>',
        'message' => '"Complete quote after without" was successfully rendered.',
      ],
      [
        'expected' => '<div><strong>Only author:</strong> Gilbert Keith Chesterton.</div>',
        'message' => '"Only author:" was successfully rendered.',
      ],
      [
        'expected' => '<div><strong>No author or date:</strong> You can only find truth with logic if you have already found truth without it..</div>',
        'message' => '"No author or date" was successfully rendered.',
      ],
      [
        'expected' => '<div><strong>Only date:</strong> 1874-1936.</div>',
        'message' => '"Only date" was successfully rendered.',
      ],
      [
        'expected' => '<div><strong>Complete quote again for good measure:</strong> You can only find truth with logic if you have already found truth without it.Gilbert Keith Chesterton1874-1936.</div>',
        'message' => '"Complete quote again for good measure" was successfully rendered.',
      ],
      [
        'expected' => '<div><strong>Marked-up:</strong>
  <blockquote>
    <p>You can only find truth with logic if you have already found truth without it.</p>
    <footer>
      &ndash; <cite><a href="#">Gilbert Keith Chesterton</a> <em>(1874-1936)</em></cite>
    </footer>
  </blockquote>',
        'message' => '"Marked-up quote" was successfully rendered.',
      ],
      [
        'expected' => '<div><span id="quotes" checked class="red green blue">All attributes:</span></div>',
        'message' => 'All attributes printed.',
      ],
      [
        'expected' => '<div><span class="red green blue" id="quotes" checked>Class attributes in front, remainder at the back:</span></div>',
        'message' => 'Class attributes printed in the front, the rest in the back.',
      ],
      [
        'expected' => '<div><span id="quotes" checked data-class="red green blue">Class attributes in back, remainder at the front:</span></div>',
        'message' => 'Class attributes printed in the back, the rest in the front.',
      ],
      [
        'expected' => '<div><span class="red green blue">Class attributes only:</span></div>',
        'message' => 'Class attributes only printed.',
      ],
      [
        'expected' => '<div><span checked id="quotes" class="red green blue">Without boolean attribute.</span></div>',
        'message' => 'Boolean attribute printed in the front.',
      ],
      [
        'expected' => '<div><span data-id="quotes" checked class="red green blue">Without string attribute.</span></div>',
        'message' => 'Without string attribute in the front.',
      ],
      [
        'expected' => '<div><span checked>Without id and class attributes.</span></div>',
        'message' => 'Attributes printed without id and class attributes.',
      ],
      [
        'expected' => '<div><span checked>Without id and class attributes via an array.</span></div>',
        'message' => 'Attributes printed without an array of things (id and class).',
      ],
      [
        'expected' => '<div><span>Without any attributes via mixed array and string.</span></div>',
        'message' => 'Attributes printed without an array of keys then a string key.',
      ],
      [
        'expected' => '<div><span>Without any attributes via mixed string then array.</span></div>',
        'message' => 'Attributes printed without a string key then an array of keys.',
      ],
      [
        'expected' => '<div><span>Without any attributes with duplicate "id" key.</span></div>',
        'message' => 'Attributes printed without two arrays of keys with a duplicate key present in both arrays.',
      ],
      [
        'expected' => '<div><span id="quotes" checked class="red green blue">All attributes again.</span></div>',
        'message' => 'All attributes printed again.',
      ],
      [
        'expected' => '<div id="quotes-here"><span class="gray-like-a-bunny bem__ized--top-feature" id="quotes-here">ID and class. Having the same ID twice is not valid markup but we want to make sure the filter doesn\'t use \Drupal\Component\Utility\Html::getUniqueId().</span></div>',
        'message' => 'Class and ID filtered.',
      ],
      [
        'expected' => '<div><strong>Rendered author string length:</strong> 24.</div>',
        'message' => 'Render filter string\'s length.',
      ],
    ];

    foreach ($elements as $element) {
      $this->assertRaw($element['expected'], $element['message']);
    }
  }

  /**
   * Test "twig_without" filter function.
   *
   * @expectedDeprecation twig_without() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Template\TwigExtension::withoutFilter(). See https://www.drupal.org/node/3011154.
   * @group legacy
   */
  public function testLegacyTwigWithoutFunction() {
    // Load the twig engine to ensure twig_without() exists.
    $twig_engine = new Extension($this->root, 'theme_engine', 'core/themes/engines/twig/twig.info.yml', 'twig.engine');
    $twig_engine->load();

    $filter_test = [
      'red' => '#F00',
      'green' => '#0F0',
      'blue' => '#00F',
    ];

    // Filter out red key.
    $result_without_red = twig_without($filter_test, 'red');
    $expected_without_red = $filter_test;
    unset($expected_without_red['red']);
    $this->assertSame($expected_without_red, $result_without_red);

    // Filter nothing and check the array is unaltered.
    $result_unaltered = twig_without($filter_test);
    $this->assertSame($filter_test, $result_unaltered);

    // Filter out blue and green.
    $result_without_blue_green = twig_without($filter_test, 'blue', 'green');
    $expected_without_blue_green = $filter_test;
    unset($expected_without_blue_green['blue']);
    unset($expected_without_blue_green['green']);
    $this->assertSame($expected_without_blue_green, $result_without_blue_green);
  }

}
