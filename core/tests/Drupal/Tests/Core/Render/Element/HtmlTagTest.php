<?php

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Render\Markup;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Render\Element\HtmlTag;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\HtmlTag
 * @group Render
 */
class HtmlTagTest extends UnitTestCase {

  /**
   * @covers ::getInfo
   */
  public function testGetInfo() {
    $htmlTag = new HtmlTag([], 'test', 'test');
    $info = $htmlTag->getInfo();
    $this->assertArrayHasKey('#pre_render', $info);
    $this->assertArrayHasKey('#attributes', $info);
    $this->assertArrayHasKey('#value', $info);
  }

  /**
   * @covers ::preRenderHtmlTag
   * @dataProvider providerPreRenderHtmlTag
   */
  public function testPreRenderHtmlTag($element, $expected) {
    $result = HtmlTag::preRenderHtmlTag($element);
    $this->assertArrayHasKey('#markup', $result);
    $this->assertEquals($expected, $result['#markup']);
  }

  /**
   * Data provider for preRenderHtmlTag test.
   */
  public function providerPreRenderHtmlTag() {
    $tags = [];

    // Value prefix/suffix.
    $element = [
      '#value' => 'value',
      '#tag' => 'p',
    ];
    $tags[] = [$element, '<p>value</p>' . "\n"];

    // Normal element without a value should not result in a void element.
    $element = [
      '#tag' => 'p',
      '#value' => NULL,
    ];
    $tags[] = [$element, "<p></p>\n"];

    // A void element.
    $element = [
      '#tag' => 'br',
    ];
    $tags[] = [$element, "<br />\n"];

    // Attributes.
    $element = [
      '#tag' => 'div',
      '#attributes' => ['class' => 'test', 'id' => 'id'],
      '#value' => 'value',
    ];
    $tags[] = [$element, '<div class="test" id="id">value</div>' . "\n"];

    // No script tags.
    $element['#noscript'] = TRUE;
    $tags[] = [$element, '<noscript><div class="test" id="id">value</div>' . "\n" . '</noscript>'];

    // Ensure that #tag is sanitised.
    $element = [
      '#tag' => 'p><script>alert()</script><p',
      '#value' => 'value',
    ];
    $tags[] = [$element, "<p&gt;&lt;script&gt;alert()&lt;/script&gt;&lt;p>value</p&gt;&lt;script&gt;alert()&lt;/script&gt;&lt;p>\n"];

    // Ensure that #value is not filtered if it is marked as safe.
    $element = [
      '#tag' => 'p',
      '#value' => Markup::create('<script>value</script>'),
    ];
    $tags[] = [$element, "<p><script>value</script></p>\n"];

    // Ensure that #value is filtered if it is not safe.
    $element = [
      '#tag' => 'p',
      '#value' => '<script>value</script>',
    ];
    $tags[] = [$element, "<p>value</p>\n"];

    return $tags;
  }

  /**
   * @covers ::preRenderConditionalComments
   * @dataProvider providerPreRenderConditionalComments
   */
  public function testPreRenderConditionalComments($element, $expected, $set_safe = FALSE) {
    if ($set_safe) {
      $element['#prefix'] = Markup::create($element['#prefix']);
      $element['#suffix'] = Markup::create($element['#suffix']);
    }
    $this->assertEquals($expected, HtmlTag::preRenderConditionalComments($element));
  }

  /**
   * Data provider for conditional comments test.
   */
  public function providerPreRenderConditionalComments() {
    // No browser specification.
    $element = [
      '#tag' => 'link',
    ];
    $tags[] = [$element, $element];

    // Specify all browsers.
    $element['#browsers'] = [
      'IE' => TRUE,
      '!IE' => TRUE,
    ];
    $tags[] = [$element, $element];

    // All IE.
    $element = [
      '#tag' => 'link',
      '#browsers' => [
        'IE' => TRUE,
        '!IE' => FALSE,
      ],
    ];
    $expected = $element;
    $expected['#prefix'] = "\n<!--[if IE]>\n";
    $expected['#suffix'] = "<![endif]-->\n";
    $tags[] = [$element, $expected];

    // Exclude IE.
    $element = [
      '#tag' => 'link',
      '#browsers' => [
        'IE' => FALSE,
      ],
    ];
    $expected = $element;
    $expected['#prefix'] = "\n<!--[if !IE]><!-->\n";
    $expected['#suffix'] = "<!--<![endif]-->\n";
    $tags[] = [$element, $expected];

    // IE gt 8
    $element = [
      '#tag' => 'link',
      '#browsers' => [
        'IE' => 'gt IE 8',
      ],
    ];
    $expected = $element;
    $expected['#prefix'] = "\n<!--[if gt IE 8]><!-->\n";
    $expected['#suffix'] = "<!--<![endif]-->\n";
    $tags[] = [$element, $expected];

    // Prefix and suffix filtering if not safe.
    $element = [
      '#tag' => 'link',
      '#browsers' => [
        'IE' => FALSE,
      ],
      '#prefix' => '<blink>prefix</blink>',
      '#suffix' => '<blink>suffix</blink>',
    ];
    $expected = $element;
    $expected['#prefix'] = "\n<!--[if !IE]><!-->\nprefix";
    $expected['#suffix'] = "suffix<!--<![endif]-->\n";
    $tags[] = [$element, $expected];

    // Prefix and suffix filtering if marked as safe. This has to come after the
    // previous test case.
    $expected['#prefix'] = "\n<!--[if !IE]><!-->\n<blink>prefix</blink>";
    $expected['#suffix'] = "<blink>suffix</blink><!--<![endif]-->\n";
    $tags[] = [$element, $expected, TRUE];

    return $tags;
  }

}
