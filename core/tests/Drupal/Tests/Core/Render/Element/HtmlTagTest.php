<?php

declare(strict_types=1);

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
    $tags['value'] = [$element, '<p>value</p>' . "\n"];

    // Normal element without a value should not result in a void element.
    $element = [
      '#tag' => 'p',
      '#value' => NULL,
    ];
    $tags['no-value'] = [$element, "<p></p>\n"];

    // A void element.
    $element = [
      '#tag' => 'br',
    ];
    $tags['void-element'] = [$element, "<br />\n"];

    // Attributes.
    $element = [
      '#tag' => 'div',
      '#attributes' => ['class' => 'test', 'id' => 'id'],
      '#value' => 'value',
    ];
    $tags['attributes'] = [$element, '<div class="test" id="id">value</div>' . "\n"];

    // No script tags.
    $element['#noscript'] = TRUE;
    $tags['noscript'] = [$element, '<noscript><div class="test" id="id">value</div>' . "\n" . '</noscript>'];

    // Ensure that #tag is sanitized.
    $element = [
      '#tag' => 'p><script>alert()</script><p',
      '#value' => 'value',
    ];
    $tags['sanitized-tag'] = [$element, "<p&gt;&lt;script&gt;alert()&lt;/script&gt;&lt;p>value</p&gt;&lt;script&gt;alert()&lt;/script&gt;&lt;p>\n"];

    // Ensure that #value is not filtered if it is marked as safe.
    $element = [
      '#tag' => 'p',
      '#value' => Markup::create('<script>value</script>'),
    ];
    $tags['value-safe'] = [$element, "<p><script>value</script></p>\n"];

    // Ensure that #value is filtered if it is not safe.
    $element = [
      '#tag' => 'p',
      '#value' => '<script>value</script>',
    ];
    $tags['value-not-safe'] = [$element, "<p>value</p>\n"];

    // Ensure that nested render arrays render properly.
    $element = [
      '#tag' => 'p',
      '#value' => NULL,
      [
        ['#markup' => '<b>value1</b>'],
        ['#markup' => '<b>value2</b>'],
      ],
    ];
    $tags['nested'] = [$element, "<p><b>value1</b><b>value2</b></p>\n"];

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
    $tags['rect'] = [$element, '<rect width="25" height="25" x="5" y="10" />' . "\n"];

    $element = [
      '#tag' => 'circle',
      '#attributes' => [
        'cx' => 100,
        'cy' => 100,
        'r' => 100,
      ],
    ];
    $tags['circle'] = [$element, '<circle cx="100" cy="100" r="100" />' . "\n"];

    $element = [
      '#tag' => 'polygon',
      '#attributes' => [
        'points' => '60,20 100,40 100,80 60,100 20,80 20,40',
      ],
    ];
    $tags['polygon'] = [$element, '<polygon points="60,20 100,40 100,80 60,100 20,80 20,40" />' . "\n"];

    $element = [
      '#tag' => 'ellipse',
      '#attributes' => [
        'cx' => 60,
        'cy' => 60,
        'rx' => 50,
        'ry' => 25,
      ],
    ];
    $tags['ellipse'] = [$element, '<ellipse cx="60" cy="60" rx="50" ry="25" />' . "\n"];

    $element = [
      '#tag' => 'use',
      '#attributes' => [
        'x' => 50,
        'y' => 10,
        'width' => 50,
        'height' => 50,
      ],
    ];
    $tags['use'] = [$element, '<use x="50" y="10" width="50" height="50" />' . "\n"];

    $element = [
      '#tag' => 'path',
      '#attributes' => [
        'd' => 'M 100 100 L 300 100 L 200 300 z',
        'fill' => 'orange',
        'stroke' => 'black',
        'stroke-width' => 3,
      ],
    ];
    $tags['path'] = [$element, '<path d="M 100 100 L 300 100 L 200 300 z" fill="orange" stroke="black" stroke-width="3" />' . "\n"];

    $element = [
      '#tag' => 'stop',
      '#attributes' => [
        'offset' => '5%',
        'stop-color' => '#F60',
      ],
    ];
    $tags['stop'] = [$element, '<stop offset="5%" stop-color="#F60" />' . "\n"];

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
    $tags['linearGradient'] = [$element, '<linearGradient><stop offset="5%" stop-color="#F60" />' . "\n" . '<stop offset="95%" stop-color="#FF6" />' . "\n" . '</linearGradient>' . "\n"];

    // Simple link.
    $element = [
      '#tag' => 'link',
    ];
    $tags['link'] = [$element, '<link />' . "\n"];

    return $tags;
  }

}
