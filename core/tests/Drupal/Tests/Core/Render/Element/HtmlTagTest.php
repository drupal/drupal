<?php

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Render\Markup;
use Drupal\Tests\Core\Render\RendererTestBase;
use Drupal\Core\Render\Element\HtmlTag;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\HtmlTag
 * @group Render
 */
class HtmlTagTest extends RendererTestBase {

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
    foreach ($result as &$child) {
      if (is_array($child) && isset($child['#tag'])) {
        $child = HtmlTag::preRenderHtmlTag($child);
      }
    }
    $this->assertEquals($expected, (string) $this->renderer->renderRoot($result));
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

    // Ensure that nested render arrays render properly.
    $element = [
      '#tag' => 'p',
      '#value' => NULL,
      [
        ['#markup' => '<b>value1</b>'],
        ['#markup' => '<b>value2</b>'],
      ],
    ];
    $tags[] = [$element, "<p><b>value1</b><b>value2</b></p>\n"];

    // Ensure svg elements.
    $element = [
      '#tag' => 'rect',
      '#attributes' => [
        'width' => 25,
        'height' => 25,
        'x' => 5,
        'y' => 10,
      ],
    ];
    $tags[] = [$element, '<rect width="25" height="25" x="5" y="10" />' . "\n"];

    $element = [
      '#tag' => 'circle',
      '#attributes' => [
        'cx' => 100,
        'cy' => 100,
        'r' => 100,
      ],
    ];
    $tags[] = [$element, '<circle cx="100" cy="100" r="100" />' . "\n"];

    $element = [
      '#tag' => 'polygon',
      '#attributes' => [
        'points' => '60,20 100,40 100,80 60,100 20,80 20,40',
      ],
    ];
    $tags[] = [$element, '<polygon points="60,20 100,40 100,80 60,100 20,80 20,40" />' . "\n"];

    $element = [
      '#tag' => 'ellipse',
      '#attributes' => [
        'cx' => 60,
        'cy' => 60,
        'rx' => 50,
        'ry' => 25,
      ],
    ];
    $tags[] = [$element, '<ellipse cx="60" cy="60" rx="50" ry="25" />' . "\n"];

    $element = [
      '#tag' => 'use',
      '#attributes' => [
        'x' => 50,
        'y' => 10,
        'width' => 50,
        'height' => 50,
      ],
    ];
    $tags[] = [$element, '<use x="50" y="10" width="50" height="50" />' . "\n"];

    $element = [
      '#tag' => 'path',
      '#attributes' => [
        'd' => 'M 100 100 L 300 100 L 200 300 z',
        'fill' => 'orange',
        'stroke' => 'black',
        'stroke-width' => 3,
      ],
    ];
    $tags[] = [$element, '<path d="M 100 100 L 300 100 L 200 300 z" fill="orange" stroke="black" stroke-width="3" />' . "\n"];

    $element = [
      '#tag' => 'stop',
      '#attributes' => [
        'offset' => '5%',
        'stop-color' => '#F60',
      ],
    ];
    $tags[] = [$element, '<stop offset="5%" stop-color="#F60" />' . "\n"];

    // Nested svg elements.
    $element = [
      '#tag' => 'linearGradient',
      '#value' => NULL,
      [
        '#tag' => 'stop',
        '#value' => NULL,
        '#attributes' => [
          'offset' => '5%',
          'stop-color' => '#F60',
        ],
      ],
      [
        '#tag' => 'stop',
        '#value' => NULL,
        '#attributes' => [
          'offset' => '95%',
          'stop-color' => '#FF6',
        ],
      ],
    ];
    $tags[] = [$element, '<linearGradient><stop offset="5%" stop-color="#F60" />' . "\n" . '<stop offset="95%" stop-color="#FF6" />' . "\n" . '</linearGradient>' . "\n"];

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
