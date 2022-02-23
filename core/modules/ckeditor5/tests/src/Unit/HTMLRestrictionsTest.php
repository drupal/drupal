<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor5\HTMLRestrictions
 * @group ckeditor5
 */
class HTMLRestrictionsTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @dataProvider providerConstruct
   */
  public function testConstructor(array $elements, ?string $expected_exception_message): void {
    if ($expected_exception_message !== NULL) {
      $this->expectException(\InvalidArgumentException::class);
      $this->expectExceptionMessage($expected_exception_message);
    }
    new HTMLRestrictions($elements);
  }

  public function providerConstruct(): \Generator {
    // Fundamental structure.
    yield 'INVALID: list instead of key-value pairs' => [
      ['<foo>', '<bar>'],
      'An array of key-value pairs must be provided, with HTML tag names as keys.',
    ];

    // Invalid HTML tag names.
    yield 'INVALID: key-value pairs now, but invalid keys due to angular brackets' => [
      ['<foo>' => '', '<bar> ' => ''],
      '"<foo>" is not a HTML tag name, it is an actual HTML tag. Omit the angular brackets.',
    ];
    yield 'INVALID: no more angular brackets, but still leading or trailing whitespace' => [
      ['foo' => '', 'bar ' => ''],
      'The "bar " HTML tag contains trailing or leading whitespace.',
    ];
    yield 'INVALID: invalid character range' => [
      ['🦙' => ''],
      '"🦙" is not a valid HTML tag name.',
    ];
    yield 'INVALID: invalid custom element name' => [
      ['foo-bar' => '', '1-foo-bar' => ''],
      '"1-foo-bar" is not a valid HTML tag name.',
    ];
    yield 'INVALID: unknown wildcard element name' => [
      ['$foo' => TRUE],
      '"$foo" is not a valid HTML tag name.',
    ];

    // Invalid HTML tag attribute name restrictions.
    yield 'INVALID: keys valid, but not yet the values' => [
      ['foo' => '', 'bar' => ''],
      'The value for the "foo" HTML tag is neither a boolean nor an array of attribute restrictions.',
    ];
    yield 'INVALID: keys valid, values can be arrays … but not empty arrays' => [
      ['foo' => [], 'bar' => []],
      'The value for the "foo" HTML tag is an empty array. This is not permitted, specify FALSE instead to indicate no attributes are allowed. Otherwise, list allowed attributes.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions' => [
      ['foo' => ['baz'], 'bar' => [' qux']],
      'The "foo" HTML tag has attribute restrictions, but it is not an array of key-value pairs, with HTML tag attribute names as keys.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions due to invalid attribute name' => [
      ['foo' => ['baz' => ''], 'bar' => [' qux' => '']],
      'The "bar" HTML tag has an attribute restriction " qux" which contains whitespace. Omit the whitespace.',
    ];

    // Invalid HTML tag attribute value restrictions.
    yield 'INVALID: keys valid, values invalid attribute restrictions due to empty strings' => [
      ['foo' => ['baz' => ''], 'bar' => ['qux' => '']],
      'The "foo" HTML tag has an attribute restriction "baz" which is neither TRUE nor an array of attribute value restrictions.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions due to an empty array of allowed attribute values' => [
      ['foo' => ['baz' => TRUE], 'bar' => ['qux' => []]],
      'The "bar" HTML tag has an attribute restriction "qux" which is set to the empty array. This is not permitted, specify either TRUE to allow all attribute values, or list the attribute value restrictions.',
    ];
    yield 'INVALID: keys valid, values invalid attribute restrictions due to a list of allowed attribute values' => [
      ['foo' => ['baz' => TRUE], 'bar' => ['qux' => ['a', 'b']]],
      'The "bar" HTML tag has attribute restriction "qux", but it is not an array of key-value pairs, with HTML tag attribute values as keys and TRUE as values.',
    ];

    // Valid values.
    yield 'VALID: keys valid, boolean attribute restriction values: also valid' => [
      ['foo' => TRUE, 'bar' => FALSE],
      NULL,
    ];
    yield 'VALID: keys valid, array attribute restriction values: also valid' => [
      ['foo' => ['baz' => TRUE], 'bar' => ['qux' => ['a' => TRUE, 'b' => TRUE]]],
      NULL,
    ];
  }

  /**
   * @covers ::isEmpty()
   * @covers ::getAllowedElements()
   * @dataProvider providerCounting
   */
  public function testCounting(array $elements, bool $expected_is_empty, int $expected_concrete_only_count, int $expected_concrete_plus_wildcard_count): void {
    $r = new HTMLRestrictions($elements);
    $this->assertSame($expected_is_empty, $r->isEmpty());
    $this->assertCount($expected_concrete_only_count, $r->getAllowedElements());
    $this->assertCount($expected_concrete_only_count, $r->getAllowedElements(TRUE));
    $this->assertCount($expected_concrete_plus_wildcard_count, $r->getAllowedElements(FALSE));
  }

  public function providerCounting(): \Generator {
    yield 'empty' => [
      [],
      TRUE,
      0,
      0,
    ];

    yield 'one' => [
      ['a' => TRUE],
      FALSE,
      1,
      1,
    ];

    yield 'two' => [
      ['a' => TRUE, 'b' => FALSE],
      FALSE,
      2,
      2,
    ];

    yield 'two of which one is a wildcard' => [
      ['a' => TRUE, '$block' => FALSE],
      FALSE,
      1,
      2,
    ];
  }

  /**
   * @covers ::fromString()
   * @covers ::fromTextFormat()
   * @covers ::fromFilterPluginInstance()
   * @dataProvider providerConvenienceConstructors
   */
  public function testConvenienceConstructors($input, array $expected, ?array $expected_raw = NULL): void {
    $expected_raw = $expected_raw ?? $expected;

    // ::fromString()
    $this->assertSame($expected, HTMLRestrictions::fromString($input)->getAllowedElements());
    $this->assertSame($expected_raw, HTMLRestrictions::fromString($input)->getAllowedElements(FALSE));

    // ::fromTextFormat()
    $text_format = $this->prophesize(FilterFormatInterface::class);
    $text_format->getHTMLRestrictions()->willReturn([
      'allowed' => $expected_raw,
    ]);
    $this->assertSame($expected, HTMLRestrictions::fromTextFormat($text_format->reveal())->getAllowedElements());
    $this->assertSame($expected_raw, HTMLRestrictions::fromTextFormat($text_format->reveal())->getAllowedElements(FALSE));

    // ::fromFilterPluginInstance()
    $filter_plugin_instance = $this->prophesize(FilterInterface::class);
    $filter_plugin_instance->getHTMLRestrictions()->willReturn([
      'allowed' => $expected_raw + [
        // @see \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions()
        '*' => [
          'style' => FALSE,
          'on*' => FALSE,
          'lang' => TRUE,
          'dir' => ['ltr' => TRUE, 'rtl' => TRUE],
        ],
      ],
    ]);
    $this->assertSame($expected, HTMLRestrictions::fromFilterPluginInstance($filter_plugin_instance->reveal())->getAllowedElements());
    $this->assertSame($expected_raw, HTMLRestrictions::fromFilterPluginInstance($filter_plugin_instance->reveal())->getAllowedElements(FALSE));
  }

  public function providerConvenienceConstructors(): \Generator {
    // All empty cases.
    yield 'empty string' => [
      '',
      [],
    ];
    yield 'empty array' => [
      implode(' ', []),
      [],
    ];
    yield 'whitespace string' => [
      '             ',
      [],
    ];

    // Some nonsense cases.
    yield 'nonsense string' => [
      'Hello there, this looks nothing like a HTML restriction.',
      [],
    ];
    yield 'nonsense array #1' => [
      implode(' ', ['foo', 'bar']),
      [],
    ];
    yield 'nonsense array #2' => [
      implode(' ', ['foo' => TRUE, 'bar' => FALSE]),
      [],
    ];

    // Single tag cases.
    yield 'tag without attributes' => [
      '<a>',
      ['a' => FALSE],
    ];
    yield 'tag with wildcard attribute' => [
      '<a *>',
      ['a' => TRUE],
    ];
    yield 'tag with single attribute allowing any value' => [
      '<a target>',
      ['a' => ['target' => TRUE]],
    ];
    yield 'tag with single attribute allowing single specific value' => [
      '<a target="_blank">',
      ['a' => ['target' => ['_blank' => TRUE]]],
    ];
    yield 'tag with single attribute allowing multiple specific values' => [
      '<a target="_self _blank">',
      ['a' => ['target' => ['_self' => TRUE, '_blank' => TRUE]]],
    ];
    yield 'tag with single attribute allowing multiple specific values (reverse order)' => [
      '<a target="_blank _self">',
      ['a' => ['target' => ['_blank' => TRUE, '_self' => TRUE]]],
    ];
    yield 'tag with two attributes' => [
      '<a target class>',
      ['a' => ['target' => TRUE, 'class' => TRUE]],
    ];
    yield 'tag with two attributes, one with a partial wildcard' => [
      '<a target class>',
      ['a' => ['target' => TRUE, 'class' => TRUE]],
    ];

    // Multiple tag cases.
    yield 'two tags' => [
      '<a> <p>',
      ['a' => FALSE, 'p' => FALSE],
    ];
    yield 'two tags (reverse order)' => [
      '<a> <p>',
      ['a' => FALSE, 'p' => FALSE],
    ];

    // Wildcard tag.
    yield '$block' => [
      '<$block class="text-align-left text-align-center text-align-right text-align-justify">',
      [],
      [
        '$block' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$block + one concrete tag to resolve into' => [
      '<p> <$block class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
      [
        'p' => FALSE,
        '$block' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$block + two concrete tag to resolve into' => [
      '<p> <$block class="text-align-left text-align-center text-align-right text-align-justify"> <blockquote>',
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
        'blockquote' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
      [
        'p' => FALSE,
        'blockquote' => FALSE,
        '$block' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$block + one concrete tag to resolve into that already allows a subset of attributes: concrete less permissive than wildcard' => [
      '<p class="text-align-left"> <$block class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
          ],
        ],
        '$block' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$block + one concrete tag to resolve into that already allows all attribute values: concrete more permissive than wildcard' => [
      '<p class> <$block class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => [
          'class' => TRUE,
        ],
      ],
      [
        'p' => [
          'class' => TRUE,
        ],
        '$block' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$block + one concrete tag to resolve into that already allows all attributes: concrete more permissive than wildcard' => [
      '<p *> <$block class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => TRUE,
      ],
      [
        'p' => TRUE,
        '$block' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];

    // @todo Test `data-*` attribute: https://www.drupal.org/project/drupal/issues/3260853
  }

  /**
   * @covers ::toCKEditor5ElementsArray()
   * @covers ::toFilterHtmlAllowedTagsString()
   * @covers ::toGeneralHtmlSupportConfig()
   * @dataProvider providerRepresentations
   */
  public function testRepresentations(HTMLRestrictions $restrictions, array $expected_elements_array, string $expected_allowed_html_string, array $expected_ghs_config): void {
    $this->assertSame($expected_elements_array, $restrictions->toCKEditor5ElementsArray());
    $this->assertSame($expected_allowed_html_string, $restrictions->toFilterHtmlAllowedTagsString());
    $this->assertSame($expected_ghs_config, $restrictions->toGeneralHtmlSupportConfig());
  }

  public function providerRepresentations(): \Generator {
    yield 'empty set' => [
      HTMLRestrictions::emptySet(),
      [],
      '',
      [],
    ];

    yield 'only tags' => [
      new HTMLRestrictions(['a' => FALSE, 'p' => FALSE, 'br' => FALSE]),
      ['<a>', '<p>', '<br>'],
      '<a> <p> <br>',
      [
        ['name' => 'a'],
        ['name' => 'p'],
        ['name' => 'br'],
      ],
    ];

    yield 'single tag with multiple attributes allowing all values' => [
      new HTMLRestrictions(['script' => ['src' => TRUE, 'defer' => TRUE]]),
      ['<script src defer>'],
      '<script src defer>',
      [
        [
          'name' => 'script',
          'attributes' => [
            'src' => TRUE,
            'defer' => TRUE,
          ],
        ],
      ],
    ];

    yield 'realistic' => [
      new HTMLRestrictions(['a' => ['href' => TRUE, 'hreflang' => ['en' => TRUE, 'fr' => TRUE]], 'p' => ['data-*' => TRUE, 'class' => ['block' => TRUE]], 'br' => FALSE]),
      ['<a href hreflang="en fr">', '<p data-* class="block">', '<br>'],
      '<a href hreflang="en fr"> <p data-* class="block"> <br>',
      [
        [
          'name' => 'a',
          'attributes' => [
            'href' => TRUE,
            'hreflang' => [
              'regexp' => [
                'pattern' => '/^(en|fr)$/',
              ],
            ],
          ],
        ],
        [
          'name' => 'p',
          'attributes' => [
            'data-*' => TRUE,
          ],
          'classes' => [
            'block',
          ],
        ],
        ['name' => 'br'],
      ],
    ];
  }

  /**
   * @covers ::diff()
   * @covers ::intersect()
   * @covers ::merge()
   * @dataProvider providerOperands
   */
  public function testOperations(HTMLRestrictions $a, HTMLRestrictions $b, $expected_diff, $expected_intersection, $expected_union): void {
    // This looks more complicated than it is: it applies the same processing to
    // all three of the expected operation results.
    foreach (['diff', 'intersection', 'union'] as $op) {
      $parameter = "expected_$op";
      // Ensure that the operation expectation is 'a' or 'b' whenever possible.
      if ($a == $$parameter) {
        throw new \LogicException("List 'a' as the expected $op rather than specifying it in full, to keep the tests legible.");
      }
      else {
        if ($b == $$parameter) {
          throw new \LogicException("List 'b' as the expected $op rather than specifying it in full, to keep the tests legible.");
        }
      }
      // Map any expected 'a' or 'b' string value to the corresponding operand.
      if ($$parameter === 'a') {
        $$parameter = $a;
      }
      elseif ($$parameter === 'b') {
        $$parameter = $b;
      }
      assert($$parameter instanceof HTMLRestrictions);
    }
    $this->assertEquals($expected_diff, $a->diff($b));
    $this->assertEquals($expected_intersection, $a->intersect($b));
    $this->assertEquals($expected_union, $a->merge($b));
  }

  public function providerOperands(): \Generator {
    // Empty set operand cases.
    yield 'any set + empty set' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => HTMLRestrictions::emptySet(),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'empty set + any set' => [
      'a' => HTMLRestrictions::emptySet(),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'diff' => 'a',
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Basic cases: tags.
    yield 'union of two very restricted tags' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'a',
    ];
    yield 'union of two very unrestricted tags' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'a',
    ];
    yield 'union of one very unrestricted tag with one very restricted tag' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'union of one very unrestricted tag with one very restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Basic cases: attributes..
    yield 'set + set with empty intersection' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => ['href' => TRUE], 'b' => ['href' => TRUE]]),
    ];
    yield 'set + identical set' => [
      'a' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'b',
      'union' => 'b',
    ];
    yield 'set + superset' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE], 'a' => ['href' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Tag restrictions.
    yield 'tag restrictions are different: <a> vs <b c>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => FALSE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a> vs <b c> — vice versa' => [
      'a' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => FALSE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a *> vs <b c>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => TRUE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a *> vs <b c> — vice versa' => [
      'a' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => TRUE, 'b' => ['c' => TRUE]]),
    ];

    // Attribute restrictions.
    yield 'attribute restrictions are less permissive: <a *> vs <a>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'attribute restrictions are more permissive: <a> vs <a *>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    yield 'attribute restrictions are more permissive: <a href> vs <a *>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'attribute restrictions are more permissive: <a> vs <a href>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'attribute restrictions are more restrictive: <a href> vs <a>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'attribute restrictions are more restrictive: <a *> vs <a href>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'attribute restrictions are different: <a href> vs <a hreflang>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['a' => FALSE]),
      'union' => new HTMLRestrictions(['a' => ['href' => TRUE, 'hreflang' => TRUE]]),
    ];
    yield 'attribute restrictions are different: <a href> vs <a hreflang> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['a' => FALSE]),
      'union' => new HTMLRestrictions(['a' => ['href' => TRUE, 'hreflang' => TRUE]]),
    ];

    // Attribute value restriction.
    yield 'attribute restrictions are different: <a hreflang="en"> vs <a hreflang="fr">' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['fr' => TRUE]]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['a' => FALSE]),
      'union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE, 'fr' => TRUE]]]),
    ];
    yield 'attribute restrictions are different: <a hreflang="en"> vs <a hreflang="fr"> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['fr' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['a' => FALSE]),
      'union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE, 'fr' => TRUE]]]),
    ];
    yield 'attribute restrictions are different: <a hreflang=*> vs <a hreflang="en">' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'attribute restrictions are different: <a hreflang=*> vs <a hreflang="en"> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'diff' => 'a',
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Complex cases.
    yield 'attribute restrictions are different: <a hreflang="en"> vs <strong>' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['strong' => TRUE]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]], 'strong' => TRUE]),
    ];
    yield 'attribute restrictions are different: <a hreflang="en"> vs <strong> — vice versa' => [
      'a' => new HTMLRestrictions(['strong' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => 'a',
      'intersect' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]], 'strong' => TRUE]),
    ];
    yield 'very restricted tag + slightly restricted tag' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'very restricted tag + slightly restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'very unrestricted tag + slightly restricted tag' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'diff' => 'a',
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'very unrestricted tag + slightly restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];

    // Wildcard + matching tag cases.
    yield 'wildcard + matching tag: attribute intersection — without possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$block' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$block' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => TRUE], 'p' => FALSE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$block' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'diff' => new HTMLRestrictions(['$block' => ['class' => TRUE]]),
      'intersection' => 'b',
      'union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$block' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — without possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE]]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$block' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE]]]),
      'b' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$block' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE]], 'p' => FALSE]),
      'diff' => new HTMLRestrictions(['p' => ['class' => ['text-align-justify' => TRUE]]]),
      'intersection' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE]]]),
      'union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$block' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE]], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'diff' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE]]]),
      'intersection' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE]]]),
      'union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$block' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: on both sides' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => TRUE, 'foo' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['$block' => ['class' => TRUE], 'p' => FALSE]),
      'diff' => new HTMLRestrictions(['$block' => ['foo' => TRUE], 'p' => ['foo' => TRUE]]),
      'intersection' => new HTMLRestrictions(['$block' => ['class' => TRUE], 'p' => ['class' => TRUE]]),
      'union' => 'a',
    ];
    yield 'wildcard + matching tag: on both sides — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['$block' => ['class' => TRUE, 'foo' => TRUE], 'p' => FALSE]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => new HTMLRestrictions(['$block' => ['class' => TRUE], 'p' => ['class' => TRUE]]),
      'union' => 'b',
    ];

    // Wildcard + non-matching cases.
    yield 'wildcard + non-matching tag: attribute diff — without possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$block' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$block' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => TRUE], 'span' => FALSE]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['span' => FALSE]),
      'union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$block' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => TRUE], 'span' => FALSE]),
      'b' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'diff' => new HTMLRestrictions(['$block' => ['class' => TRUE]]),
      'intersection' => new HTMLRestrictions(['span' => FALSE]),
      'union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$block' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — without possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => ['vertical-align-top' => TRUE]]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$block' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => ['vertical-align-top' => TRUE]]]),
      'b' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'diff' => 'a',
      'intersection' => HTMLRestrictions::emptySet(),
      'union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$block' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => ['vertical-align-top' => TRUE]], 'span' => FALSE]),
      'diff' => 'a',
      'intersection' => new HTMLRestrictions(['span' => FALSE]),
      'union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$block' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => ['vertical-align-top' => TRUE]], 'span' => FALSE]),
      'b' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'diff' => new HTMLRestrictions(['$block' => ['class' => ['vertical-align-top' => TRUE]]]),
      'intersection' => new HTMLRestrictions(['span' => FALSE]),
      'union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$block' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];

    // Wildcard + wildcard cases.
    yield 'wildcard + wildcard tag: attributes' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => TRUE, 'foo' => TRUE]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => TRUE]]),
      'diff' => new HTMLRestrictions(['$block' => ['foo' => TRUE]]),
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'wildcard + wildcard tag: attributes — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => TRUE, 'foo' => TRUE]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
    yield 'wildcard + wildcard tag: attribute values' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE]]]),
      'diff' => new HTMLRestrictions(['$block' => ['class' => ['text-align-justify' => TRUE]]]),
      'intersection' => 'b',
      'union' => 'a',
    ];
    yield 'wildcard + wildcard tag: attribute values — vice versa' => [
      'a' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE]]]),
      'b' => new HTMLRestrictions(['$block' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'diff' => HTMLRestrictions::emptySet(),
      'intersection' => 'a',
      'union' => 'b',
    ];
  }

}
