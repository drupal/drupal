<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\Element\HtmlTagTest.
 */

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Render\SafeString;
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
    $htmlTag = new HtmlTag(array(), 'test', 'test');
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
    $tags = array();

    // Value prefix/suffix.
    $element = array(
      '#value_prefix' => 'value_prefix|',
      '#value_suffix' => '|value_suffix',
      '#value' => 'value',
      '#tag' => 'p',
    );
    $tags[] = array($element, '<p>value_prefix|value|value_suffix</p>' . "\n");

    // Normal element without a value should not result in a void element.
    $element = array(
      '#tag' => 'p',
      '#value' => NULL,
    );
    $tags[] = array($element, "<p></p>\n");

    // A void element.
    $element = array(
      '#tag' => 'br',
    );
    $tags[] = array($element, "<br />\n");

    // Attributes.
    $element = array(
      '#tag' => 'div',
      '#attributes' => array('class' => 'test', 'id' => 'id'),
      '#value' => 'value',
    );
    $tags[] = array($element, '<div class="test" id="id">value</div>' . "\n");

    // No script tags.
    $element['#noscript'] = TRUE;
    $tags[] = array($element, '<noscript><div class="test" id="id">value</div>' . "\n" . '</noscript>');

    // Ensure that #tag is sanitised.
    $element = array(
      '#tag' => 'p><script>alert()</script><p',
      '#value' => 'value',
    );
    $tags[] = array($element, "<p&gt;&lt;script&gt;alert()&lt;/script&gt;&lt;p>value</p&gt;&lt;script&gt;alert()&lt;/script&gt;&lt;p>\n");

    // Ensure that #value is not filtered if it is marked as safe.
    $element = array(
      '#tag' => 'p',
      '#value' => SafeString::create('<script>value</script>'),
    );
    $tags[] = array($element, "<p><script>value</script></p>\n");

    // Ensure that #value is filtered if it is not safe.
    $element = array(
      '#tag' => 'p',
      '#value' => '<script>value</script>',
    );
    $tags[] = array($element, "<p>value</p>\n");

    // Ensure that #value_prefix and #value_suffix are not filtered.
    $element = array(
      '#tag' => 'p',
      '#value' => 'value',
      '#value_prefix' => '<script>value</script>',
      '#value_suffix' => '<script>value</script>',
    );
    $tags[] = array($element, "<p><script>value</script>value<script>value</script></p>\n");

    return $tags;
  }

  /**
   * @covers ::preRenderConditionalComments
   * @dataProvider providerPreRenderConditionalComments
   */
  public function testPreRenderConditionalComments($element, $expected, $set_safe = FALSE) {
    if ($set_safe) {
      $element['#prefix'] = SafeString::create($element['#prefix']);
      $element['#suffix'] = SafeString::create($element['#suffix']);
    }
    $this->assertEquals($expected, HtmlTag::preRenderConditionalComments($element));
  }

  /**
   * Data provider for conditional comments test.
   */
  public function providerPreRenderConditionalComments() {
    // No browser specification.
    $element = array(
      '#tag' => 'link',
    );
    $tags[] = array($element, $element);

    // Specify all browsers.
    $element['#browsers'] = array(
      'IE' => TRUE,
      '!IE' => TRUE,
    );
    $tags[] = array($element, $element);

    // All IE.
    $element = array(
      '#tag' => 'link',
      '#browsers' => array(
        'IE' => TRUE,
        '!IE' => FALSE,
      ),
    );
    $expected = $element;
    $expected['#prefix'] = "\n<!--[if IE]>\n";
    $expected['#suffix'] = "<![endif]-->\n";
    $tags[] = array($element, $expected);

    // Exclude IE.
    $element = array(
      '#tag' => 'link',
      '#browsers' => array(
        'IE' => FALSE,
      ),
    );
    $expected = $element;
    $expected['#prefix'] = "\n<!--[if !IE]><!-->\n";
    $expected['#suffix'] = "<!--<![endif]-->\n";
    $tags[] = array($element, $expected);

    // IE gt 8
    $element = array(
      '#tag' => 'link',
      '#browsers' => array(
        'IE' => 'gt IE 8',
      ),
    );
    $expected = $element;
    $expected['#prefix'] = "\n<!--[if gt IE 8]><!-->\n";
    $expected['#suffix'] = "<!--<![endif]-->\n";
    $tags[] = array($element, $expected);

    // Prefix and suffix filtering if not safe.
    $element = array(
      '#tag' => 'link',
      '#browsers' => array(
        'IE' => FALSE,
      ),
      '#prefix' => '<blink>prefix</blink>',
      '#suffix' => '<blink>suffix</blink>',
    );
    $expected = $element;
    $expected['#prefix'] = "\n<!--[if !IE]><!-->\nprefix";
    $expected['#suffix'] = "suffix<!--<![endif]-->\n";
    $tags[] = array($element, $expected);

    // Prefix and suffix filtering if marked as safe. This has to come after the
    // previous test case.
    $expected['#prefix'] = "\n<!--[if !IE]><!-->\n<blink>prefix</blink>";
    $expected['#suffix'] = "<blink>suffix</blink><!--<![endif]-->\n";
    $tags[] = array($element, $expected, TRUE);

    return $tags;
  }

}
