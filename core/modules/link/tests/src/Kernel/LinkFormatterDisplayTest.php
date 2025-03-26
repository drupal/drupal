<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Kernel;

use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\link\Traits\LinkInputValuesTraits;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;

// cspell:ignore Fragm

/**
 * Tests the default 'link' field formatter.
 *
 * The formatter is tested with several forms of complex query parameters. And
 * each form is tested with different display settings.
 *
 * @group link
 */
class LinkFormatterDisplayTest extends FieldKernelTestBase {

  use LinkInputValuesTraits;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'type' => 'link',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ])->save();
  }

  /**
   * Tests that links are rendered correctly.
   *
   * Run tests without dataProvider to improve speed.
   *
   * @see \Drupal\Tests\link\Traits\LinkInputValuesTraits::getLinkInputValues()
   * @see self::getTestCases()
   */
  public function testLinkFormatter(): void {
    // Create an entity with link field values provided.
    $entity = EntityTest::create();
    $entity->field_test->setValue($this->getLinkInputValues());

    foreach ($this->getTestCases() as $case_name => $case_options) {
      [$display_settings, $expected_results] = array_values($case_options);
      $this->assertEquals(count($this->getLinkInputValues()), count($expected_results), "Each field delta have expected result. Case name: '$case_name'");

      // Render link field with default 'link' formatter and custom
      // display settings. Hide field label.
      $render_array = $entity->field_test->view([
        'label' => 'hidden',
        'settings' => $display_settings,
      ]);
      $output = (string) \Drupal::service('renderer')->renderRoot($render_array);
      // Convert each field delta value to separate array item.
      $field_deltas_display = explode("\n", trim($output));

      // Check results.
      foreach ($expected_results as $delta => $expected_result) {
        $rendered_delta = trim($field_deltas_display[$delta]);
        $message = "Test case failed. Case name: '$case_name'. Delta: '$delta'. Uri: '{$this->getLinkInputValues()[$delta]['uri']}'";
        $this->assertEquals($expected_result, $rendered_delta, $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestCases(): \Generator {
    $default = [
      0 => '<div><a href="http://www.example.com/content/articles/archive?author=John&amp;year=2012#com">http://www.example.com/content/articles/archive?author=John&amp;year=2012#com</a></div>',
      1 => '<div><a href="http://www.example.org/content/articles/archive?author=John&amp;year=2012#org">A very long &amp; strange example title that could break the nice layout of the site</a></div>',
      2 => '<div><a href="#net">Fragment only</a></div>',
      3 => '<div><a href="?a%5B0%5D=1&amp;a%5B1%5D=2">?a%5B0%5D=1&amp;a%5B1%5D=2</a></div>',
      4 => '<div><a href="?b%5B0%5D=1&amp;b%5B1%5D=2">?b%5B0%5D=1&amp;b%5B1%5D=2</a></div>',
      16 => '<div><a href="?b%5B0%5D=9&amp;b%5B1%5D=8">?b%5B0%5D=9&amp;b%5B1%5D=8</a></div>',
      5 => '<div><a href="?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3">?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3</a></div>',
      6 => '<div><a href="?e%5Bf%5D%5Bg%5D=h">?e%5Bf%5D%5Bg%5D=h</a></div>',
      7 => '<div><a href="?i%5Bj%5Bk%5D=l">?i%5Bj%5Bk%5D=l</a></div>',
      8 => '<div><a href="?x=2">?x=2</a></div>',
      9 => '<div><a href="?z%5B0%5D=2">?z%5B0%5D=2</a></div>',
      10 => '<div><a href=""></a></div>',
      11 => '<div><a href="">Title, no link</a></div>',
      12 => '<div><span></span></div>',
      13 => '<div><span>Title, no link</span></div>',
      14 => '<div><button type="button"></button></div>',
      15 => '<div><button type="button">Title, button</button></div>',
    ];

    yield 'default settings' => [
      'display settings' => [],
      'expected_results' => $default,
    ];

    yield 'trim_length=null' => [
      'display_settings' => ['trim_length' => NULL],
      'expected_results' => $default,
    ];

    yield 'trim_length=6' => [
      'display settings' => ['trim_length' => 6],
      'expected_results' => [
        0 => '<div><a href="http://www.example.com/content/articles/archive?author=John&amp;year=2012#com">http:…</a></div>',
        1 => '<div><a href="http://www.example.org/content/articles/archive?author=John&amp;year=2012#org">A ver…</a></div>',
        2 => '<div><a href="#net">Fragm…</a></div>',
        3 => '<div><a href="?a%5B0%5D=1&amp;a%5B1%5D=2">?a%5B…</a></div>',
        4 => '<div><a href="?b%5B0%5D=1&amp;b%5B1%5D=2">?b%5B…</a></div>',
        16 => '<div><a href="?b%5B0%5D=9&amp;b%5B1%5D=8">?b%5B…</a></div>',
        5 => '<div><a href="?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3">?c%5B…</a></div>',
        6 => '<div><a href="?e%5Bf%5D%5Bg%5D=h">?e%5B…</a></div>',
        7 => '<div><a href="?i%5Bj%5Bk%5D=l">?i%5B…</a></div>',
        8 => '<div><a href="?x=2">?x=2</a></div>',
        9 => '<div><a href="?z%5B0%5D=2">?z%5B…</a></div>',
        10 => '<div><a href=""></a></div>',
        11 => '<div><a href="">Title…</a></div>',
        12 => '<div><span></span></div>',
        13 => '<div><span>Title…</span></div>',
        14 => '<div><button type="button"></button></div>',
        15 => '<div><button type="button">Title…</button></div>',
      ],
    ];

    yield 'attribute rel=null' => [
      'display_settings' => ['rel' => NULL],
      'expected_results' => $default,
    ];

    yield 'attribute rel=nofollow' => [
      'display_settings' => ['rel' => 'nofollow'],
      'expected_results' => [
        0 => '<div><a href="http://www.example.com/content/articles/archive?author=John&amp;year=2012#com" rel="nofollow">http://www.example.com/content/articles/archive?author=John&amp;year=2012#com</a></div>',
        1 => '<div><a href="http://www.example.org/content/articles/archive?author=John&amp;year=2012#org" rel="nofollow">A very long &amp; strange example title that could break the nice layout of the site</a></div>',
        2 => '<div><a href="#net" rel="nofollow">Fragment only</a></div>',
        3 => '<div><a href="?a%5B0%5D=1&amp;a%5B1%5D=2" rel="nofollow">?a%5B0%5D=1&amp;a%5B1%5D=2</a></div>',
        4 => '<div><a href="?b%5B0%5D=1&amp;b%5B1%5D=2" rel="nofollow">?b%5B0%5D=1&amp;b%5B1%5D=2</a></div>',
        16 => '<div><a href="?b%5B0%5D=9&amp;b%5B1%5D=8" rel="nofollow">?b%5B0%5D=9&amp;b%5B1%5D=8</a></div>',
        5 => '<div><a href="?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3" rel="nofollow">?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3</a></div>',
        6 => '<div><a href="?e%5Bf%5D%5Bg%5D=h" rel="nofollow">?e%5Bf%5D%5Bg%5D=h</a></div>',
        7 => '<div><a href="?i%5Bj%5Bk%5D=l" rel="nofollow">?i%5Bj%5Bk%5D=l</a></div>',
        8 => '<div><a href="?x=2" rel="nofollow">?x=2</a></div>',
        9 => '<div><a href="?z%5B0%5D=2" rel="nofollow">?z%5B0%5D=2</a></div>',
        10 => '<div><a href="" rel="nofollow"></a></div>',
        11 => '<div><a href="" rel="nofollow">Title, no link</a></div>',
        12 => '<div><span rel="nofollow"></span></div>',
        13 => '<div><span rel="nofollow">Title, no link</span></div>',
        14 => '<div><button rel="nofollow" type="button"></button></div>',
        15 => '<div><button rel="nofollow" type="button">Title, button</button></div>',
      ],
    ];

    yield 'attribute target=null' => [
      'display_settings' => ['target' => NULL],
      'expected_results' => $default,
    ];

    yield 'attribute target=_blank' => [
      'display_settings' => ['target' => '_blank'],
      'expected_results' => [
        0 => '<div><a href="http://www.example.com/content/articles/archive?author=John&amp;year=2012#com" target="_blank">http://www.example.com/content/articles/archive?author=John&amp;year=2012#com</a></div>',
        1 => '<div><a href="http://www.example.org/content/articles/archive?author=John&amp;year=2012#org" target="_blank">A very long &amp; strange example title that could break the nice layout of the site</a></div>',
        2 => '<div><a href="#net" target="_blank">Fragment only</a></div>',
        3 => '<div><a href="?a%5B0%5D=1&amp;a%5B1%5D=2" target="_blank">?a%5B0%5D=1&amp;a%5B1%5D=2</a></div>',
        4 => '<div><a href="?b%5B0%5D=1&amp;b%5B1%5D=2" target="_blank">?b%5B0%5D=1&amp;b%5B1%5D=2</a></div>',
        16 => '<div><a href="?b%5B0%5D=9&amp;b%5B1%5D=8" target="_blank">?b%5B0%5D=9&amp;b%5B1%5D=8</a></div>',
        5 => '<div><a href="?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3" target="_blank">?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3</a></div>',
        6 => '<div><a href="?e%5Bf%5D%5Bg%5D=h" target="_blank">?e%5Bf%5D%5Bg%5D=h</a></div>',
        7 => '<div><a href="?i%5Bj%5Bk%5D=l" target="_blank">?i%5Bj%5Bk%5D=l</a></div>',
        8 => '<div><a href="?x=2" target="_blank">?x=2</a></div>',
        9 => '<div><a href="?z%5B0%5D=2" target="_blank">?z%5B0%5D=2</a></div>',
        10 => '<div><a href="" target="_blank"></a></div>',
        11 => '<div><a href="" target="_blank">Title, no link</a></div>',
        12 => '<div><span target="_blank"></span></div>',
        13 => '<div><span target="_blank">Title, no link</span></div>',
        14 => '<div><button target="_blank" type="button"></button></div>',
        15 => '<div><button target="_blank" type="button">Title, button</button></div>',
      ],
    ];

    yield 'url_only=false' => [
      'display_settings' => ['url_only' => FALSE],
      'expected_results' => $default,
    ];

    yield 'url_only=true' => [
      'display_settings' => ['url_only' => TRUE],
      'expected_results' => [
        0 => '<div><a href="http://www.example.com/content/articles/archive?author=John&amp;year=2012#com">http://www.example.com/content/articles/archive?author=John&amp;year=2012#com</a></div>',
        1 => '<div><a href="http://www.example.org/content/articles/archive?author=John&amp;year=2012#org">http://www.example.org/content/articles/archive?author=John&amp;year=2012#org</a></div>',
        2 => '<div><a href="#net">#net</a></div>',
        3 => '<div><a href="?a%5B0%5D=1&amp;a%5B1%5D=2">?a%5B0%5D=1&amp;a%5B1%5D=2</a></div>',
        4 => '<div><a href="?b%5B0%5D=1&amp;b%5B1%5D=2">?b%5B0%5D=1&amp;b%5B1%5D=2</a></div>',
        16 => '<div><a href="?b%5B0%5D=9&amp;b%5B1%5D=8">?b%5B0%5D=9&amp;b%5B1%5D=8</a></div>',
        5 => '<div><a href="?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3">?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3</a></div>',
        6 => '<div><a href="?e%5Bf%5D%5Bg%5D=h">?e%5Bf%5D%5Bg%5D=h</a></div>',
        7 => '<div><a href="?i%5Bj%5Bk%5D=l">?i%5Bj%5Bk%5D=l</a></div>',
        8 => '<div><a href="?x=2">?x=2</a></div>',
        9 => '<div><a href="?z%5B0%5D=2">?z%5B0%5D=2</a></div>',
        10 => '<div><a href=""></a></div>',
        11 => '<div><a href=""></a></div>',
        12 => '<div><span></span></div>',
        13 => '<div><span></span></div>',
        14 => '<div><button type="button"></button></div>',
        15 => '<div><button type="button"></button></div>',
      ],
    ];

    yield 'url_only=false, url_plain=true' => [
      'display_settings' => [
        'url_only' => FALSE,
        'url_plain' => TRUE,
      ],
      'expected_results' => $default,
    ];

    yield 'url_only=true, url_plain=true' => [
      'display_settings' => [
        'url_only' => TRUE,
        'url_plain' => TRUE,
      ],
      'expected_results' => [
        0 => '<div>http://www.example.com/content/articles/archive?author=John&amp;year=2012#com</div>',
        1 => '<div>http://www.example.org/content/articles/archive?author=John&amp;year=2012#org</div>',
        2 => '<div>#net</div>',
        3 => '<div>?a%5B0%5D=1&amp;a%5B1%5D=2</div>',
        4 => '<div>?b%5B0%5D=1&amp;b%5B1%5D=2</div>',
        16 => '<div>?b%5B0%5D=9&amp;b%5B1%5D=8</div>',
        5 => '<div>?c%5B0%5D=1&amp;c%5B1%5D=2&amp;d=3</div>',
        6 => '<div>?e%5Bf%5D%5Bg%5D=h</div>',
        7 => '<div>?i%5Bj%5Bk%5D=l</div>',
        8 => '<div>?x=2</div>',
        9 => '<div>?z%5B0%5D=2</div>',
        10 => '<div></div>',
        11 => '<div></div>',
        12 => '<div></div>',
        13 => '<div></div>',
        14 => '<div></div>',
        15 => '<div></div>',
      ],
    ];
  }

}
