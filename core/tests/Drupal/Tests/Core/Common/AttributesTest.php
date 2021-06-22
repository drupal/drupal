<?php

namespace Drupal\Tests\Core\Common;

use Drupal\Core\Template\Attribute;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Drupal\Core\Template\Attribute functionality.
 *
 * @group Common
 */
class AttributesTest extends UnitTestCase {

  /**
   * Provides data for the Attribute test.
   *
   * @return array
   */
  public function providerTestAttributeData() {
    return [
      // Verify that special characters are HTML encoded.
      [['&"\'<>' => 'value'], ' &amp;&quot;&#039;&lt;&gt;="value"', 'HTML encode attribute names.'],
      [['title' => '&"\'<>'], ' title="&amp;&quot;&#039;&lt;&gt;"', 'HTML encode attribute values.'],
      // Verify multi-value attributes are concatenated with spaces.
      [['class' => ['first', 'last']], ' class="first last"', 'Concatenate multi-value attributes.'],
      // Verify boolean attribute values are rendered correctly.
      [['disabled' => TRUE], ' disabled', 'Boolean attribute is rendered.'],
      [['disabled' => FALSE], '', 'Boolean attribute is not rendered.'],
      // Verify empty attribute values are rendered.
      [['alt' => ''], ' alt=""', 'Empty attribute value #1.'],
      [['alt' => NULL], '', 'Null attribute value #2.'],
      // Verify multiple attributes are rendered.
      [
        [
          'id' => 'id-test',
          'class' => ['first', 'last'],
          'alt' => 'Alternate',
        ],
        ' id="id-test" class="first last" alt="Alternate"',
        'Multiple attributes.',
      ],
      // Verify empty attributes array is rendered.
      [[], '', 'Empty attributes array.'],
    ];
  }

  /**
   * Tests casting an Attribute object to a string.
   *
   * @see \Drupal\Core\Template\Attribute::__toString()
   *
   * @dataProvider providerTestAttributeData
   */
  public function testDrupalAttributes($attributes, $expected, $message) {
    $this->assertSame($expected, (string) new Attribute($attributes), $message);
  }

  /**
   * Tests attribute iteration.
   */
  public function testAttributeIteration() {
    $attribute = new Attribute(['key1' => 'value1']);
    foreach ($attribute as $value) {
      $this->assertSame((string) $value, 'value1', 'Iterate over attribute.');
    }
  }

  /**
   * Tests AttributeValueBase copy.
   */
  public function testAttributeValueBaseCopy() {
    $original_attributes = new Attribute([
      'checked' => TRUE,
      'class' => ['who', 'is', 'on'],
      'id' => 'first',
    ]);
    $attributes['selected'] = $original_attributes['checked'];
    $attributes['id'] = $original_attributes['id'];
    $attributes = new Attribute($attributes);
    $this->assertSame(' checked class="who is on" id="first"', (string) $original_attributes, 'Original boolean value used with original name.');
    $this->assertSame(' selected id="first"', (string) $attributes, 'Original boolean value used with new name.');
  }

}
