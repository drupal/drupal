<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\Annotation\CKEditor5Plugin;
use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\ckeditor5\SmartDefaultSettings;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\EditorInterface;
use Drupal\Tests\ckeditor5\Traits\PrivateMethodUnitTestTrait;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\ckeditor5\SmartDefaultSettings
 * @group ckeditor5
 */
class SmartDefaultSettingsTest extends UnitTestCase {

  use PrivateMethodUnitTestTrait;

  /**
   * @covers ::computeSurplusScore
   * @dataProvider providerSurplusScore
   */
  public function testSurplusScore(HTMLRestrictions $surplus, HTMLRestrictions $needed, int $expected): void {
    $method = self::getMethod(SmartDefaultSettings::class, 'computeSurplusScore');
    $this->assertSame($expected, $method->invoke(NULL, $surplus, $needed));
  }

  /**
   * Data provider for testing computeSurplusScore().
   *
   * @return \Generator
   *   Yields the data for testSurplusScore().
   */
  public static function providerSurplusScore(): \Generator {
    $needed = new HTMLRestrictions(['code' => FALSE]);

    yield 'surplus: 1 tag, 1 attribute, 1 attribute with wildcard restriction' => [
      HTMLRestrictions::fromString('<pre> <code class="language-*">'),
      $needed,
      1001010,
    ];

    yield 'surplus: 1 tag, 1 attribute, 2 allowed attribute values' => [
      HTMLRestrictions::fromString('<code class="language-php language-js">'),
      $needed,
      1002,
    ];

    yield 'surplus: 2 attributes, 4 allowed attribute values' => [
      // cSpell:disable-next-line
      HTMLRestrictions::fromString('<code class="language-php language-js" data-library="highlightjs something">'),
      $needed,
      2004,
    ];

    yield 'surplus: 1 any attribute allowed' => [
      HTMLRestrictions::fromString('<code *>'),
      $needed,
      100000,
    ];

    yield 'surplus: 1 attribute, 1 attribute any value allowed' => [
      HTMLRestrictions::fromString('<code class>'),
      $needed,
      1100,
    ];

    yield 'surplus: 1 tag, 2 wildcard attributes, 2 attributes, 3 attributes any value allowed, 1 wildcard allowed attribute value ' => [
      HTMLRestrictions::fromString('<pre> <code data-config-* data-options-* data-highlight-library class="language-*">'),
      $needed,
      1022310,
    ];
  }

  /**
   * @covers ::getCandidates
   * @covers ::selectCandidate
   * @dataProvider providerCandidates
   */
  public function testCandidates(HTMLRestrictions $provided, HTMLRestrictions $still_needed, array $disabled_plugin_definitions, array $expected_candidates, array $expected_selection = []): void {
    $get_candidates = self::getMethod(SmartDefaultSettings::class, 'getCandidates');
    $smart_default_settings = new SmartDefaultSettings(
      $this->prophesize(CKEditor5PluginManagerInterface::class)->reveal(),
      $this->prophesize(LoggerInterface::class)->reveal(),
      $this->prophesize(ModuleHandlerInterface::class)->reveal(),
      $this->prophesize(AccountInterface::class)->reveal(),
    );
    $editor = $this->prophesize(EditorInterface::class);
    $this->assertSame($expected_candidates, $get_candidates->invoke($smart_default_settings, $provided, $still_needed, $disabled_plugin_definitions, $editor->reveal()));
    $select_candidate = self::getMethod(SmartDefaultSettings::class, 'selectCandidate');
    $this->assertSame($expected_selection, $select_candidate->invoke(NULL, $expected_candidates, $still_needed, array_keys($provided->getAllowedElements())));
  }

  /**
   * Data provider for testing getCandidates() and ::selectCandidate().
   *
   * @return \Generator
   *   Yields the data for testCandidates().
   */
  public static function providerCandidates(): \Generator {
    $generate_definition = function (string $label_and_id, array $overrides): CKEditor5PluginDefinition {
      $annotation = [
        'provider' => 'test',
        'id' => "test_$label_and_id",
        'drupal' => ['label' => "$label_and_id"],
        'ckeditor5' => ['plugins' => []],
      ];
      foreach ($overrides as $path => $value) {
        NestedArray::setValue($annotation, explode('.', $path), $value);
      }
      $annotation_instance = new CKEditor5Plugin($annotation);
      $definition = $annotation_instance->get();
      return $definition;
    };

    yield 'Tag needed, no match due to no plugin supporting it' => [
      HTMLRestrictions::emptySet(),
      HTMLRestrictions::fromString('<foo>'),
      [
        $generate_definition('foo', ['drupal.elements' => FALSE]),
      ],
      [],
      [],
    ];

    yield 'Tag needed, single match without surplus' => [
      HTMLRestrictions::emptySet(),
      HTMLRestrictions::fromString('<foo>'),
      [
        $generate_definition('foo', ['drupal.elements' => ['<foo>']]),
      ],
      [
        'foo' => [
          '-attributes-none-' => [
            'test_foo' => 0,
          ],
        ],
      ],
      // Perfect surplus score, but also only choice available.
      ['test_foo' => ['-attributes-none-' => ['foo' => NULL]]],
    ];

    yield 'Tag needed, no match due to plugins only supporting attributes on the needed tag' => [
      HTMLRestrictions::emptySet(),
      HTMLRestrictions::fromString('<foo>'),
      [
        $generate_definition('foo', ['drupal.elements' => ['<foo bar baz>']]),
      ],
      [],
      // No choice available due to the tag not being creatable.
      [],
    ];

    $various_foo_definitions = [
      $generate_definition('all_attrs', ['drupal.elements' => ['<foo *>']]),
      $generate_definition('attrs', ['drupal.elements' => ['<foo bar baz>']]),
      $generate_definition('attr_values', ['drupal.elements' => ['<foo bar="a b">']]),
      $generate_definition('plain', ['drupal.elements' => ['<foo>']]),
      $generate_definition('tags', ['drupal.elements' => ['<foo>', '<bar>', '<baz>']]),
      $generate_definition('tags_and_attrs', ['drupal.elements' => ['<foo bar baz>', '<bar>', '<baz>']]),
      $generate_definition('tags_and_attr_values', ['drupal.elements' => ['<foo bar="a b" baz>', '<bar>', '<baz>']]),
    ];

    yield 'Tag needed, multiple matches' => [
      HTMLRestrictions::emptySet(),
      HTMLRestrictions::fromString('<foo>'),
      $various_foo_definitions,
      [
        'foo' => [
          '-attributes-none-' => [
            'test_plain' => 0,
            'test_tags' => 2000000,
          ],
        ],
      ],
      // test_plain (elements: `<foo>`) has perfect surplus score.
      ['test_plain' => ['-attributes-none-' => ['foo' => NULL]]],
    ];

    yield 'Attribute needed, multiple matches' => [
      HTMLRestrictions::fromString('<foo>'),
      HTMLRestrictions::fromString('<foo bar>'),
      $various_foo_definitions,
      [
        'foo' => [
          'bar' => [
            // Because `<foo bar>` allowed.
            TRUE => [
              'test_all_attrs' => 100000,
              // This will be selected.
              'test_attrs' => 1100,
              'test_tags_and_attrs' => 2001100,
            ],
            // Because `<foo bar="a">` allowed.
            'a' => [
              TRUE => [
                'test_attr_values' => 0,
                'test_tags_and_attr_values' => 2001100,
              ],
            ],
            // Because `<foo bar="b">` allowed.
            'b' => [
              TRUE => [
                'test_attr_values' => 0,
                'test_tags_and_attr_values' => 2001100,
              ],
            ],
          ],
          // Note that `test_plain` and `test_tags` are absent.
          '-attributes-none-' => [
            'test_all_attrs' => 100000,
            'test_attrs' => 1100,
            'test_attr_values' => 0,
            'test_tags_and_attrs' => 2001100,
            'test_tags_and_attr_values' => 2001100,
          ],
        ],
      ],
      // test_attrs (elements: `<foo bar baz>`) has best surplus score, despite
      // allowing one extra attribute and any value on that attribute.
      ['test_attrs' => ['bar' => ['foo' => TRUE]]],
    ];

    yield 'Attribute value needed, multiple matches' => [
      HTMLRestrictions::fromString('<foo>'),
      HTMLRestrictions::fromString('<foo bar="b">'),
      $various_foo_definitions,
      [
        'foo' => [
          'bar' => [
            'b' => [
              TRUE => [
                'test_all_attrs' => 100000,
                'test_attrs' => 2200,
                // This will be selected.
                'test_attr_values' => 1001,
                'test_tags_and_attrs' => 2002200,
                'test_tags_and_attr_values' => 2002101,
              ],
            ],
          ],
          // Note that `test_plain` and `test_tags` are absent.
          '-attributes-none-' => [
            'test_all_attrs' => 100000,
            'test_attrs' => 2200,
            'test_attr_values' => 1001,
            'test_tags_and_attrs' => 2002200,
            'test_tags_and_attr_values' => 2002101,
          ],
        ],
      ],
      // test_attr_values (elements: `<foo bar="a b">`) has best surplus score,
      // despite allowing one extra attribute value.
      ['test_attr_values' => ['bar' => ['foo' => ['b' => TRUE]]]],
    ];
  }

}
