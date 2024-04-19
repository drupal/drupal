<?php

declare(strict_types=1);

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

  public static function providerConstruct(): \Generator {
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
    yield 'INVALID: keys valid, values invalid attribute restrictions due to broad wildcard instead of prefix/infix/suffix wildcard attribute name' => [
      ['foo' => ['*' => TRUE]],
      'The "foo" HTML tag has an attribute restriction "*". This implies all attributes are allowed. Remove the attribute restriction instead, or use a prefix (`*-foo`), infix (`*-foo-*`) or suffix (`foo-*`) wildcard restriction instead.',
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
    yield 'INVALID: keys valid, values invalid attribute restrictions due to broad wildcard instead of prefix/infix/suffix wildcard allowed attribute value' => [
      ['foo' => ['bar' => ['*' => TRUE]]],
      'The "foo" HTML tag has an attribute restriction "bar" with a "*" allowed attribute value. This implies all attributes values are allowed. Remove the attribute value restriction instead, or use a prefix (`*-foo`), infix (`*-foo-*`) or suffix (`foo-*`) wildcard restriction instead.',
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

    // Invalid global attribute `*` HTML tag restrictions.
    yield 'INVALID: global attribute tag allowing no attributes' => [
      ['*' => FALSE],
      'The value for the special "*" global attribute HTML tag must be an array of attribute restrictions.',
    ];
    yield 'INVALID: global attribute tag allowing any attribute' => [
      ['*' => TRUE],
      'The value for the special "*" global attribute HTML tag must be an array of attribute restrictions.',
    ];

    // Valid global attribute `*` HTML tag restrictions.
    yield 'VALID: global attribute tag with attribute allowed' => [
      ['*' => ['foo' => TRUE]],
      NULL,
    ];
    yield 'VALID: global attribute tag with attribute forbidden' => [
      ['*' => ['foo' => FALSE]],
      NULL,
    ];
    yield 'VALID: global attribute tag with attribute allowed, specific attribute values allowed' => [
      ['*' => ['foo' => ['a' => TRUE, 'b' => TRUE]]],
      NULL,
    ];
    yield 'VALID BUT NOT YET SUPPORTED: global attribute tag with attribute allowed, specific attribute values forbidden' => [
      ['*' => ['foo' => ['a' => FALSE, 'b' => FALSE]]],
      'The "*" HTML tag has attribute restriction "foo", but it is not an array of key-value pairs, with HTML tag attribute values as keys and TRUE as values.',
    ];

    // Invalid overrides of globally disallowed attributes.
    yield 'INVALID: <foo bar> when "bar" is globally disallowed' => [
      ['foo' => ['bar' => TRUE], '*' => ['bar' => FALSE, 'baz' => TRUE]],
      'The attribute restrictions in "<foo bar>" are allowing attributes "bar" that are disallowed by the special "*" global attribute restrictions',
    ];
    yield 'INVALID: <foo style> when "style" is globally disallowed' => [
      ['foo' => ['style' => TRUE], '*' => ['bar' => FALSE, 'baz' => TRUE, 'style' => FALSE]],
      'The attribute restrictions in "<foo style>" are allowing attributes "bar", "style" that are disallowed by the special "*" global attribute restrictions',
    ];
    yield 'INVALID: <foo on*> when "on*" is globally disallowed' => [
      ['foo' => ['on*' => TRUE], '*' => ['bar' => FALSE, 'baz' => TRUE, 'style' => FALSE, 'on*' => FALSE]],
      'The attribute restrictions in "<foo on*>" are allowing attributes "bar", "style", "on*" that are disallowed by the special "*" global attribute restrictions',
    ];
    yield 'INVALID: <foo ontouch> when "on" is globally disallowed' => [
      ['foo' => ['ontouch' => TRUE], '*' => ['bar' => FALSE, 'baz' => TRUE, 'style' => FALSE, 'on*' => FALSE]],
      'The attribute restrictions in "<foo ontouch>" are allowing attributes "bar", "style", "on*" that are disallowed by the special "*" global attribute restrictions',
    ];
  }

  /**
   * @covers ::allowsNothing
   * @covers ::getAllowedElements
   * @dataProvider providerCounting
   */
  public function testCounting(array $elements, bool $expected_is_empty, int $expected_concrete_only_count, int $expected_concrete_plus_wildcard_count): void {
    $r = new HTMLRestrictions($elements);
    $this->assertSame($expected_is_empty, $r->allowsNothing());
    $this->assertCount($expected_concrete_only_count, $r->getAllowedElements());
    $this->assertCount($expected_concrete_only_count, $r->getAllowedElements(TRUE));
    $this->assertCount($expected_concrete_plus_wildcard_count, $r->getAllowedElements(FALSE));
  }

  public static function providerCounting(): \Generator {
    yield 'empty' => [
      [],
      TRUE,
      0,
      0,
    ];

    yield 'one concrete tag' => [
      ['a' => TRUE],
      FALSE,
      1,
      1,
    ];

    yield 'one wildcard tag: considered to allow nothing because no concrete tag to resolve onto' => [
      ['$text-container' => ['class' => ['text-align-left' => TRUE]]],
      FALSE,
      0,
      1,
    ];

    yield 'two concrete tags' => [
      ['a' => TRUE, 'b' => FALSE],
      FALSE,
      2,
      2,
    ];

    yield 'one concrete tag, one wildcard tag' => [
      ['a' => TRUE, '$text-container' => ['class' => ['text-align-left' => TRUE]]],
      FALSE,
      1,
      2,
    ];

    yield 'only globally allowed attribute: considered to allow something' => [
      ['*' => ['lang' => TRUE]],
      FALSE,
      1,
      1,
    ];

    yield 'only globally forbidden attribute: considered to allow nothing' => [
      ['*' => ['style' => FALSE]],
      TRUE,
      1,
      1,
    ];
  }

  /**
   * @covers ::fromString
   * @covers ::fromTextFormat
   * @covers ::fromFilterPluginInstance
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

    // @see \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions()
    $filter_html_additional_expectations = [
      '*' => [
        'style' => FALSE,
        'on*' => FALSE,
        'lang' => TRUE,
        'dir' => ['ltr' => TRUE, 'rtl' => TRUE],
      ],
    ];
    // ::fromFilterPluginInstance()
    $filter_plugin_instance = $this->prophesize(FilterInterface::class);
    $filter_plugin_instance->getHTMLRestrictions()->willReturn([
      'allowed' => $expected_raw + $filter_html_additional_expectations,
    ]);
    $this->assertSame($expected + $filter_html_additional_expectations, HTMLRestrictions::fromFilterPluginInstance($filter_plugin_instance->reveal())->getAllowedElements());
    $this->assertSame($expected_raw + $filter_html_additional_expectations, HTMLRestrictions::fromFilterPluginInstance($filter_plugin_instance->reveal())->getAllowedElements(FALSE));
  }

  public static function providerConvenienceConstructors(): \Generator {
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
    yield 'tag with single attribute allowing any value unnecessarily explicitly' => [
      '<a target="*">',
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
    yield 'tag with allowed attribute value that happen to be numbers' => [
      '<ol type="1 A I">',
      ['ol' => ['type' => [1 => TRUE, 'A' => TRUE, 'I' => TRUE]]],
    ];
    yield 'tag with allowed attribute value that happen to be numbers (reversed)' => [
      '<ol type="I A 1">',
      ['ol' => ['type' => ['I' => TRUE, 'A' => TRUE, 1 => TRUE]]],
    ];
    yield 'tag with two attributes, spread across declarations' => [
      '<a target> <a class>',
      ['a' => ['target' => TRUE, 'class' => TRUE]],
    ];
    yield 'tag with conflicting attribute config, allow one attribute and forbid all attributes' => [
      '<a target> <a>',
      ['a' => ['target' => TRUE]],
    ];
    yield 'tag with conflicting attribute config, allow one attribute and allow all attributes' => [
      '<a *> <a target>',
      ['a' => TRUE],
    ];
    yield 'tag attribute configuration spread across declarations' => [
      '<a target="_blank"> <a target="_self"> <a target="_*">',
      ['a' => ['target' => ['_blank' => TRUE, '_self' => TRUE, '_*' => TRUE]]],
    ];
    yield 'tag attribute configuration spread across declarations, allow all attributes values' => [
      '<a target> <a target="_blank"> <a target="_self"> <a target="_*">',
      ['a' => ['target' => TRUE]],
    ];

    // Multiple tag cases.
    yield 'two tags' => [
      '<a> <p>',
      ['a' => FALSE, 'p' => FALSE],
    ];
    yield 'two tags (reverse order)' => [
      '<p> <a>',
      ['p' => FALSE, 'a' => FALSE],
    ];

    // Wildcard tag, attribute and attribute value.
    yield '$text-container' => [
      '<$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
      [],
      [
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container, with attribute values spread across declarations' => [
      '<$text-container class="text-align-left"> <$text-container class="text-align-center"> <$text-container class="text-align-right"> <$text-container class="text-align-justify">',
      [],
      [
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + one concrete tag to resolve into' => [
      '<p> <$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
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
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + two concrete tag to resolve into' => [
      '<p> <$text-container class="text-align-left text-align-center text-align-right text-align-justify"> <div>',
      [
        'p' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
        'div' => [
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
        'div' => FALSE,
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + one concrete tag to resolve into that already allows a subset of attributes: concrete less permissive than wildcard' => [
      '<p class="text-align-left"> <$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
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
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + one concrete tag to resolve into that already allows all attribute values: concrete more permissive than wildcard' => [
      '<p class> <$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => [
          'class' => TRUE,
        ],
      ],
      [
        'p' => [
          'class' => TRUE,
        ],
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '$text-container + one concrete tag to resolve into that already allows all attributes: concrete more permissive than wildcard' => [
      '<p *> <$text-container class="text-align-left text-align-center text-align-right text-align-justify">',
      [
        'p' => TRUE,
      ],
      [
        'p' => TRUE,
        '$text-container' => [
          'class' => [
            'text-align-left' => TRUE,
            'text-align-center' => TRUE,
            'text-align-right' => TRUE,
            'text-align-justify' => TRUE,
          ],
        ],
      ],
    ];
    yield '<drupal-media data-*>' => [
      '<drupal-media data-*>',
      ['drupal-media' => ['data-*' => TRUE]],
    ];
    yield '<drupal-media foo-*-bar>' => [
      '<drupal-media foo-*-bar>',
      ['drupal-media' => ['foo-*-bar' => TRUE]],
    ];
    yield '<drupal-media *-foo>' => [
      '<drupal-media *-foo>',
      ['drupal-media' => ['*-foo' => TRUE]],
    ];
    yield '<h2 id="jump-*">' => [
      '<h2 id="jump-*">',
      ['h2' => ['id' => ['jump-*' => TRUE]]],
    ];

    // Attribute restrictions that match the global attribute restrictions
    // should be omitted from concrete tags.
    yield '<p> <* foo>' => [
      '<p> <* foo>',
      ['p' => FALSE, '*' => ['foo' => TRUE]],
    ];
    yield '<p foo> <* foo> results in <p> getting simplified' => [
      '<p foo> <* foo>',
      ['p' => FALSE, '*' => ['foo' => TRUE]],
    ];
    yield '<* foo> <p foo> results in <p> getting simplified' => [
      '<* foo> <p foo>',
      ['p' => FALSE, '*' => ['foo' => TRUE]],
    ];
    yield '<p foo bar> <* foo> results in <p> getting simplified' => [
      '<p foo bar> <* foo>',
      ['p' => ['bar' => TRUE], '*' => ['foo' => TRUE]],
    ];
    yield '<* foo> <p foo bar> results in <p> getting simplified' => [
      '<* foo> <p foo bar>',
      ['p' => ['bar' => TRUE], '*' => ['foo' => TRUE]],
    ];
    yield '<p foo="a b"> + <* foo="b a"> results in <p> getting simplified' => [
      '<p foo="a b"> <* foo="b a">',
      ['p' => FALSE, '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    yield '<* foo="b a"> <p foo="a b"> results in <p> getting simplified' => [
      '<* foo="b a"> <p foo="a b">',
      ['p' => FALSE, '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    yield '<p foo="a b" bar> + <* foo="b a"> results in <p> getting simplified' => [
      '<p foo="a b" bar> <* foo="b a">',
      ['p' => ['bar' => TRUE], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    yield '<* foo="b a"> <p foo="a b" bar> results in <p> getting simplified' => [
      '<* foo="b a"> <p foo="a b" bar>',
      ['p' => ['bar' => TRUE], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    yield '<p foo="a b c"> + <* foo="b a"> results in <p> getting simplified' => [
      '<p foo="a b c"> <* foo="b a">',
      ['p' => ['foo' => ['c' => TRUE]], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    yield '<* foo="b a"> <p foo="a b c"> results in <p> getting simplified' => [
      '<* foo="b a"> <p foo="a b c">',
      ['p' => ['foo' => ['c' => TRUE]], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    // Attribute restrictions that match the global attribute restrictions
    // should be omitted from wildcard tags.
    yield '<p> <$text-container foo> <* foo> results in <$text-container> getting simplified' => [
      '<p> <$text-container foo> <* foo>',
      ['p' => FALSE, '*' => ['foo' => TRUE]],
      ['p' => FALSE, '$text-container' => FALSE, '*' => ['foo' => TRUE]],
    ];
    yield '<* foo> <text-container foo> <p> results in <$text-container> getting stripped' => [
      '<* foo> <p> <$text-container foo>',
      ['p' => FALSE, '*' => ['foo' => TRUE]],
      ['p' => FALSE, '*' => ['foo' => TRUE], '$text-container' => FALSE],
    ];
    yield '<p> <$text-container foo bar> <* foo> results in <$text-container> getting simplified' => [
      '<p> <$text-container foo bar> <* foo>',
      ['p' => ['bar' => TRUE], '*' => ['foo' => TRUE]],
      ['p' => FALSE, '$text-container' => ['bar' => TRUE], '*' => ['foo' => TRUE]],
    ];
    yield '<* foo> <$text-container foo bar> <p> results in <$text-container> getting simplified' => [
      '<* foo> <$text-container foo bar> <p>',
      ['p' => ['bar' => TRUE], '*' => ['foo' => TRUE]],
      ['p' => FALSE, '*' => ['foo' => TRUE], '$text-container' => ['bar' => TRUE]],
    ];
    yield '<p> <$text-container foo="a b"> + <* foo="b a"> results in <$text-container> getting simplified' => [
      '<p> <$text-container foo="a b"> <* foo="b a">',
      ['p' => FALSE, '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
      ['p' => FALSE, '$text-container' => FALSE, '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    yield '<* foo="b a"> <p> <$text-container foo="a b"> results in <$text-container> getting simplified' => [
      '<* foo="b a"> <p> <$text-container foo="a b">',
      ['p' => FALSE, '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
      ['p' => FALSE, '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]], '$text-container' => FALSE],
    ];
    yield '<p> <$text-container foo="a b" bar> + <* foo="b a"> results in <$text-container> getting simplified' => [
      '<p> <$text-container foo="a b" bar> <* foo="b a">',
      ['p' => ['bar' => TRUE], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
      ['p' => FALSE, '$text-container' => ['bar' => TRUE], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    yield '<* foo="b a"> <p> <$text-container foo="a b" bar> results in <$text-container> getting simplified' => [
      '<* foo="b a"> <p> <$text-container foo="a b" bar>',
      ['p' => ['bar' => TRUE], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
      ['p' => FALSE, '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]], '$text-container' => ['bar' => TRUE]],
    ];
    yield '<p> <$text-container foo="a b c"> + <* foo="b a"> results in <$text-container> getting simplified' => [
      '<p> <$text-container foo="a b c"> <* foo="b a">',
      ['p' => ['foo' => ['c' => TRUE]], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
      ['p' => FALSE, '$text-container' => ['foo' => ['c' => TRUE]], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
    ];
    yield '<* foo="b a"> <p> <$text-container foo="a b c"> results in <$text-container> getting simplified' => [
      '<* foo="b a"> <p> <$text-container foo="a b c">',
      ['p' => ['foo' => ['c' => TRUE]], '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]]],
      ['p' => FALSE, '*' => ['foo' => ['b' => TRUE, 'a' => TRUE]], '$text-container' => ['foo' => ['c' => TRUE]]],
    ];
  }

  /**
   * @covers ::toCKEditor5ElementsArray
   * @covers ::toFilterHtmlAllowedTagsString
   * @covers ::toGeneralHtmlSupportConfig
   * @dataProvider providerRepresentations
   */
  public function testRepresentations(HTMLRestrictions $restrictions, array $expected_elements_array, string $expected_allowed_html_string, array $expected_ghs_config): void {
    $this->assertSame($expected_elements_array, $restrictions->toCKEditor5ElementsArray());
    $this->assertSame($expected_allowed_html_string, $restrictions->toFilterHtmlAllowedTagsString());
    $this->assertSame($expected_ghs_config, $restrictions->toGeneralHtmlSupportConfig());
  }

  public static function providerRepresentations(): \Generator {
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
            ['key' => 'src', 'value' => TRUE],
            ['key' => 'defer', 'value' => TRUE],
          ],
        ],
      ],
    ];

    yield '$text-container wildcard' => [
      new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'data-llama' => TRUE], 'div' => FALSE, 'span' => FALSE, 'p' => ['id' => TRUE]]),
      ['<$text-container class data-llama>', '<div>', '<span>', '<p id>'],
      '<div class data-llama> <span> <p id class data-llama>',
      [
        [
          'name' => 'div',
          'classes' => TRUE,
          'attributes' => [
            [
              'key' => 'data-llama',
              'value' => TRUE,
            ],
          ],
        ],
        ['name' => 'span'],
        [
          'name' => 'p',
          'attributes' => [
            [
              'key' => 'id',
              'value' => TRUE,
            ],
            [
              'key' => 'data-llama',
              'value' => TRUE,
            ],
          ],
          'classes' => TRUE,
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
            ['key' => 'href', 'value' => TRUE],
            [
              'key' => 'hreflang',
              'value' => [
                'regexp' => [
                  'pattern' => '/^(en|fr)$/',
                ],
              ],
            ],
          ],
        ],
        [
          'name' => 'p',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^data-.*$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
          'classes' => [
            'regexp' => [
              'pattern' => '/^(block)$/',
            ],
          ],
        ],
        ['name' => 'br'],
      ],
    ];

    // Wildcard tag, attribute and attribute value.
    yield '$text-container' => [
      new HTMLRestrictions(['p' => FALSE, '$text-container' => ['data-*' => TRUE]]),
      ['<p>', '<$text-container data-*>'],
      '<p data-*>',
      [
        [
          'name' => 'p',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^data-.*$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
        ],
      ],
    ];
    yield '<drupal-media data-*>' => [
      new HTMLRestrictions(['drupal-media' => ['data-*' => TRUE]]),
      ['<drupal-media data-*>'],
      '<drupal-media data-*>',
      [
        [
          'name' => 'drupal-media',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^data-.*$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
        ],
      ],
    ];
    yield '<drupal-media foo-*-bar>' => [
      new HTMLRestrictions(['drupal-media' => ['foo-*-bar' => TRUE]]),
      ['<drupal-media foo-*-bar>'],
      '<drupal-media foo-*-bar>',
      [
        [
          'name' => 'drupal-media',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^foo-.*-bar$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
        ],
      ],
    ];
    yield '<drupal-media *-bar>' => [
      new HTMLRestrictions(['drupal-media' => ['*-bar' => TRUE]]),
      ['<drupal-media *-bar>'],
      '<drupal-media *-bar>',
      [
        [
          'name' => 'drupal-media',
          'attributes' => [
            [
              'key' => [
                'regexp' => [
                  'pattern' => '/^.*-bar$/',
                ],
              ],
              'value' => TRUE,
            ],
          ],
        ],
      ],
    ];
    yield '<h2 id="jump-*">' => [
      new HTMLRestrictions(['h2' => ['id' => ['jump-*' => TRUE]]]),
      ['<h2 id="jump-*">'],
      '<h2 id="jump-*">',
      [
        [
          'name' => 'h2',
          'attributes' => [
            [
              'key' => 'id',
              'value' => [
                'regexp' => [
                  'pattern' => '/^(jump-.*)$/',
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    yield '<ol type="1 A">' => [
      new HTMLRestrictions(['ol' => ['type' => ['1' => TRUE, 'A' => TRUE]]]),
      ['<ol type="1 A">'],
      '<ol type="1 A">',
      [
        [
          'name' => 'ol',
          'attributes' => [
            [
              'key' => 'type',
              'value' => [
                'regexp' => [
                  'pattern' => '/^(1|A)$/',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::diff
   * @covers ::intersect
   * @covers ::merge
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

  public static function providerOperands(): \Generator {
    // Empty set operand cases.
    yield 'any set + empty set' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => HTMLRestrictions::emptySet(),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'empty set + any set' => [
      'a' => HTMLRestrictions::emptySet(),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];

    // Basic cases: tags.
    yield 'union of two very restricted tags' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'a',
    ];
    yield 'union of two very unrestricted tags' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'a',
    ];
    yield 'union of one very unrestricted tag with one very restricted tag' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'union of one very unrestricted tag with one very restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];

    // Basic cases: attributes..
    yield 'set + set with empty intersection' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['a' => ['href' => TRUE], 'b' => ['href' => TRUE]]),
    ];
    yield 'set + identical set' => [
      'a' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'b',
      'expected_union' => 'b',
    ];
    yield 'set + superset' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['b' => ['href' => TRUE], 'a' => ['href' => TRUE]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];

    // Tag restrictions.
    yield 'tag restrictions are different: <a> vs <b c>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['a' => FALSE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a> vs <b c> — vice versa' => [
      'a' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['a' => FALSE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a *> vs <b c>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['a' => TRUE, 'b' => ['c' => TRUE]]),
    ];
    yield 'tag restrictions are different: <a *> vs <b c> — vice versa' => [
      'a' => new HTMLRestrictions(['b' => ['c' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['a' => TRUE, 'b' => ['c' => TRUE]]),
    ];

    // Attribute restrictions.
    yield 'attribute restrictions are less permissive: <a *> vs <a>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'attribute restrictions are more permissive: <a> vs <a *>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];

    yield 'attribute restrictions are more permissive: <a href> vs <a *>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];
    yield 'attribute restrictions are more permissive: <a> vs <a href>' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];
    yield 'attribute restrictions are more restrictive: <a href> vs <a>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'attribute restrictions are more restrictive: <a *> vs <a href>' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'attribute restrictions are different: <a href> vs <a hreflang>' => [
      'a' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => new HTMLRestrictions(['a' => FALSE]),
      'expected_union' => new HTMLRestrictions(['a' => ['href' => TRUE, 'hreflang' => TRUE]]),
    ];
    yield 'attribute restrictions are different: <a href> vs <a hreflang> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['href' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => new HTMLRestrictions(['a' => FALSE]),
      'expected_union' => new HTMLRestrictions(['a' => ['href' => TRUE, 'hreflang' => TRUE]]),
    ];

    // Attribute value restriction.
    yield 'attribute restrictions are different: <a hreflang="en"> vs <a hreflang="fr">' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['fr' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => new HTMLRestrictions(['a' => FALSE]),
      'expected_union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE, 'fr' => TRUE]]]),
    ];
    yield 'attribute restrictions are different: <a hreflang="en"> vs <a hreflang="fr"> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['fr' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => new HTMLRestrictions(['a' => FALSE]),
      'expected_union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE, 'fr' => TRUE]]]),
    ];
    yield 'attribute restrictions are different: <a hreflang=*> vs <a hreflang="en">' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'attribute restrictions are different: <a hreflang=*> vs <a hreflang="en"> — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => TRUE]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];
    yield 'attribute restrictions are different: <ol type=*> vs <ol type="A">' => [
      'a' => new HTMLRestrictions(['ol' => ['type' => TRUE]]),
      'b' => new HTMLRestrictions(['ol' => ['type' => ['A' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'attribute restrictions are different: <ol type=*> vs <ol type="A"> — vice versa' => [
      'b' => new HTMLRestrictions(['ol' => ['type' => ['A' => TRUE]]]),
      'a' => new HTMLRestrictions(['ol' => ['type' => TRUE]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];
    yield 'attribute restrictions are different: <ol type=*> vs <ol type="1">' => [
      'a' => new HTMLRestrictions(['ol' => ['type' => TRUE]]),
      'b' => new HTMLRestrictions(['ol' => ['type' => ['1' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'attribute restrictions are different: <ol type=*> vs <ol type="1"> — vice versa' => [
      'b' => new HTMLRestrictions(['ol' => ['type' => ['1' => TRUE]]]),
      'a' => new HTMLRestrictions(['ol' => ['type' => TRUE]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];
    yield 'attribute restrictions are the same: <ol type="1"> vs <ol type="1">' => [
      'a' => new HTMLRestrictions(['ol' => ['type' => ['1' => TRUE]]]),
      'b' => new HTMLRestrictions(['ol' => ['type' => ['1' => TRUE]]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'a',
    ];

    // Complex cases.
    yield 'attribute restrictions are different: <a hreflang="en"> vs <strong>' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['strong' => TRUE]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]], 'strong' => TRUE]),
    ];
    yield 'attribute restrictions are different: <a hreflang="en"> vs <strong> — vice versa' => [
      'a' => new HTMLRestrictions(['strong' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]], 'strong' => TRUE]),
    ];
    yield 'very restricted tag + slightly restricted tag' => [
      'a' => new HTMLRestrictions(['a' => FALSE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];
    yield 'very restricted tag + slightly restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => FALSE]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'very unrestricted tag + slightly restricted tag' => [
      'a' => new HTMLRestrictions(['a' => TRUE]),
      'b' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'very unrestricted tag + slightly restricted tag — vice versa' => [
      'a' => new HTMLRestrictions(['a' => ['hreflang' => ['en' => TRUE]]]),
      'b' => new HTMLRestrictions(['a' => TRUE]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];

    // Wildcard tag + matching tag cases.
    yield 'wildcard + matching tag: attribute intersection — without possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => FALSE]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute intersection — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['p' => ['class' => TRUE]]),
      'expected_diff' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'expected_intersection' => 'b',
      'expected_union' => new HTMLRestrictions(['p' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — without possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$text-container' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'b' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$text-container' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]], 'p' => FALSE]),
      'expected_diff' => new HTMLRestrictions(['p' => ['class' => ['text-align-justify' => TRUE]]]),
      'expected_intersection' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE]]]),
      'expected_union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$text-container' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: attribute value intersection — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'expected_diff' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'expected_intersection' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE]]]),
      'expected_union' => new HTMLRestrictions(['p' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]], '$text-container' => ['class' => ['text-align-center' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: on both sides' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'foo' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => FALSE]),
      'expected_diff' => new HTMLRestrictions(['$text-container' => ['foo' => TRUE], 'p' => ['foo' => TRUE]]),
      'expected_intersection' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => ['class' => TRUE]]),
      'expected_union' => 'a',
    ];
    yield 'wildcard + matching tag: on both sides — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => FALSE]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'foo' => TRUE], 'p' => FALSE]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'p' => ['class' => TRUE]]),
      'expected_union' => 'b',
    ];
    yield 'wildcard + matching tag: wildcard resolves into matching tag, but matching tag already supports all attributes' => [
      'a' => new HTMLRestrictions(['p' => TRUE]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['foo' => TRUE, 'bar' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['p' => TRUE, '$text-container' => ['class' => ['foo' => TRUE, 'bar' => TRUE]]]),
    ];
    yield 'wildcard + matching tag: wildcard resolves into matching tag, but matching tag already supports all attributes — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['foo' => TRUE, 'bar' => TRUE]]]),
      'b' => new HTMLRestrictions(['p' => TRUE]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['p' => TRUE, '$text-container' => ['class' => ['foo' => TRUE, 'bar' => TRUE]]]),
    ];

    // Wildcard tag + non-matching tag cases.
    yield 'wildcard + non-matching tag: attribute diff — without possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'span' => FALSE]),
      'expected_diff' => 'a',
      'expected_intersection' => new HTMLRestrictions(['span' => FALSE]),
      'expected_union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute diff — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE], 'span' => FALSE]),
      'b' => new HTMLRestrictions(['span' => ['class' => TRUE]]),
      'expected_diff' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'expected_intersection' => new HTMLRestrictions(['span' => FALSE]),
      'expected_union' => new HTMLRestrictions(['span' => ['class' => TRUE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — without possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — without possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
      'b' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — WITH possible resolving' => [
      'a' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]], 'span' => FALSE]),
      'expected_diff' => 'a',
      'expected_intersection' => new HTMLRestrictions(['span' => FALSE]),
      'expected_union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];
    yield 'wildcard + non-matching tag: attribute value diff — WITH possible resolving — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]], 'span' => FALSE]),
      'b' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]]]),
      'expected_diff' => new HTMLRestrictions(['$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
      'expected_intersection' => new HTMLRestrictions(['span' => FALSE]),
      'expected_union' => new HTMLRestrictions(['span' => ['class' => ['vertical-align-top' => TRUE, 'vertical-align-bottom' => TRUE]], '$text-container' => ['class' => ['vertical-align-top' => TRUE]]]),
    ];

    // Wildcard tag + wildcard tag cases.
    yield 'wildcard + wildcard tag: attributes' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'foo' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'expected_diff' => new HTMLRestrictions(['$text-container' => ['foo' => TRUE]]),
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'wildcard + wildcard tag: attributes — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE, 'foo' => TRUE]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];
    yield 'wildcard + wildcard tag: attribute values' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'expected_diff' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-justify' => TRUE]]]),
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'wildcard + wildcard tag: attribute values — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE]]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => ['text-align-center' => TRUE, 'text-align-justify' => TRUE]]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];

    // Concrete attributes + wildcard attribute cases for all 3 possible
    // wildcard locations. Parametrized to prevent excessive repetition and
    // subtle differences.
    $wildcard_locations = [
      'prefix' => 'data-*',
      'infix' => '*-entity-*',
      'suffix' => '*-type',
    ];
    foreach ($wildcard_locations as $wildcard_location => $wildcard_attr_name) {
      yield "concrete attrs + wildcard $wildcard_location attr that covers a superset" => [
        'a' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE]]),
        'b' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'expected_diff' => HTMLRestrictions::emptySet(),
        'expected_intersection' => 'a',
        'expected_union' => 'b',
      ];
      yield "concrete attrs + wildcard $wildcard_location attr that covers a superset — vice versa" => [
        'a' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'b' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE]]),
        'expected_diff' => 'a',
        'expected_intersection' => 'b',
        'expected_union' => 'a',
      ];
      yield "concrete attrs + wildcard $wildcard_location attr that covers a subset" => [
        'a' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE, 'class' => TRUE]]),
        'b' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'expected_diff' => new HTMLRestrictions(['img' => ['class' => TRUE]]),
        'expected_intersection' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE]]),
        'expected_union' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE, 'class' => TRUE]]),
      ];
      yield "concrete attrs + wildcard $wildcard_location attr that covers a subset — vice versa" => [
        'a' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'b' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE, 'class' => TRUE]]),
        'expected_diff' => 'a',
        'expected_intersection' => new HTMLRestrictions(['img' => ['data-entity-bundle-type' => TRUE, 'data-entity-type' => TRUE]]),
        'expected_union' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE, 'class' => TRUE]]),
      ];
      yield "wildcard $wildcard_location attr + wildcard $wildcard_location attr" => [
        'a' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE, 'class' => TRUE]]),
        'b' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'expected_diff' => new HTMLRestrictions(['img' => ['class' => TRUE]]),
        'expected_intersection' => 'b',
        'expected_union' => 'a',
      ];
      yield "wildcard $wildcard_location attr + wildcard $wildcard_location attr — vice versa" => [
        'a' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE]]),
        'b' => new HTMLRestrictions(['img' => [$wildcard_attr_name => TRUE, 'class' => TRUE]]),
        'expected_diff' => HTMLRestrictions::emptySet(),
        'expected_intersection' => 'a',
        'expected_union' => 'b',
      ];
    }

    // Global attribute `*` HTML tag + global attribute `*` HTML tag cases.
    yield 'global attribute tag + global attribute tag: no overlap in attributes' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'b' => new HTMLRestrictions(['*' => ['baz' => FALSE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'baz' => FALSE]]),
    ];
    yield 'global attribute tag + global attribute tag: no overlap in attributes — vice versa' => [
      'a' => new HTMLRestrictions(['*' => ['baz' => FALSE]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'baz' => FALSE]]),
    ];
    yield 'global attribute tag + global attribute tag: overlap in attributes, same attribute value restrictions' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'b' => new HTMLRestrictions(['*' => ['bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'expected_diff' => new HTMLRestrictions(['*' => ['foo' => TRUE]]),
      'expected_intersection' => 'b',
      'expected_union' => 'a',
    ];
    yield 'global attribute tag + global attribute tag: overlap in attributes, same attribute value restrictions — vice versa' => [
      'a' => new HTMLRestrictions(['*' => ['bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'expected_diff' => HTMLRestrictions::emptySet(),
      'expected_intersection' => 'a',
      'expected_union' => 'b',
    ];
    yield 'global attribute tag + global attribute tag: overlap in attributes, different attribute value restrictions' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'b' => new HTMLRestrictions(['*' => ['bar' => TRUE, 'dir' => TRUE, 'foo' => FALSE]]),
      'expected_diff' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'expected_intersection' => new HTMLRestrictions(['*' => ['bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE], 'foo' => FALSE]]),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => TRUE, 'dir' => TRUE]]),
    ];
    yield 'global attribute tag + global attribute tag: overlap in attributes, different attribute value restrictions — vice versa' => [
      'a' => new HTMLRestrictions(['*' => ['bar' => TRUE, 'dir' => TRUE, 'foo' => FALSE]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      'expected_diff' => 'a',
      'expected_intersection' => new HTMLRestrictions(['*' => ['bar' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE], 'foo' => FALSE]]),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => TRUE, 'dir' => TRUE]]),
    ];

    // Global attribute `*` HTML tag + concrete tag.
    yield 'global attribute tag + concrete tag' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'b' => new HTMLRestrictions(['p' => FALSE]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], 'p' => FALSE]),
    ];
    yield 'global attribute tag + concrete tag — vice versa' => [
      'a' => new HTMLRestrictions(['p' => FALSE]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], 'p' => FALSE]),
    ];
    yield 'global attribute tag + concrete tag with allowed attribute' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'b' => new HTMLRestrictions(['p' => ['baz' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], 'p' => ['baz' => TRUE]]),
    ];
    yield 'global attribute tag + concrete tag with allowed attribute — vice versa' => [
      'a' => new HTMLRestrictions(['p' => ['baz' => TRUE]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], 'p' => ['baz' => TRUE]]),
    ];

    // Global attribute `*` HTML tag + wildcard tag.
    yield 'global attribute tag + wildcard tag' => [
      'a' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'b' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], '$text-container' => ['class' => TRUE]]),
    ];
    yield 'global attribute tag + wildcard tag — vice versa' => [
      'a' => new HTMLRestrictions(['$text-container' => ['class' => TRUE]]),
      'b' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE]]),
      'expected_diff' => 'a',
      'expected_intersection' => HTMLRestrictions::emptySet(),
      'expected_union' => new HTMLRestrictions(['*' => ['foo' => TRUE, 'bar' => FALSE], '$text-container' => ['class' => TRUE]]),
    ];
  }

  /**
   * @covers ::getWildcardSubset
   * @covers ::getConcreteSubset
   * @covers ::getPlainTagsSubset
   * @covers ::extractPlainTagsSubset
   * @dataProvider providerSubsets
   */
  public function testSubsets(HTMLRestrictions $input, HTMLRestrictions $expected_wildcard_subset, HTMLRestrictions $expected_concrete_subset, HTMLRestrictions $expected_plain_tags_subset, HTMLRestrictions $expected_extracted_plain_tags_subset): void {
    $this->assertEquals($expected_wildcard_subset, $input->getWildcardSubset());
    $this->assertEquals($expected_concrete_subset, $input->getConcreteSubset());
    $this->assertEquals($expected_plain_tags_subset, $input->getPlainTagsSubset());
    $this->assertEquals($expected_extracted_plain_tags_subset, $input->extractPlainTagsSubset());
  }

  public static function providerSubsets(): \Generator {
    yield 'empty set' => [
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
    ];

    yield 'without wildcards' => [
      new HTMLRestrictions(['div' => FALSE]),
      new HTMLRestrictions([]),
      new HTMLRestrictions(['div' => FALSE]),
      new HTMLRestrictions(['div' => FALSE]),
      new HTMLRestrictions(['div' => FALSE]),
    ];

    yield 'without wildcards with attributes' => [
      new HTMLRestrictions(['div' => ['foo' => ['bar' => TRUE]]]),
      new HTMLRestrictions([]),
      new HTMLRestrictions(['div' => ['foo' => ['bar' => TRUE]]]),
      new HTMLRestrictions([]),
      new HTMLRestrictions(['div' => FALSE]),
    ];

    yield 'with wildcards' => [
      new HTMLRestrictions(['div' => FALSE, '$text-container' => ['data-llama' => TRUE], '*' => ['on*' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE]]),
      new HTMLRestrictions(['div' => FALSE, '*' => ['on*' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      new HTMLRestrictions(['div' => FALSE]),
      new HTMLRestrictions(['div' => FALSE]),
    ];

    yield 'wildcards and global attribute tag' => [
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE], '*' => ['on*' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE]]),
      new HTMLRestrictions(['*' => ['on*' => FALSE, 'dir' => ['ltr' => TRUE, 'rtl' => TRUE]]]),
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
    ];

    yield 'only wildcards' => [
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE]]),
      new HTMLRestrictions(['$text-container' => ['data-llama' => TRUE]]),
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
      new HTMLRestrictions([]),
    ];
  }

}
