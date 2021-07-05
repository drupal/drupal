<?php

namespace Drupal\Tests\Component\Attribute;

use Drupal\Component\Attribute\AttributeCollection;
use Drupal\Component\Attribute\AttributeArray;
use Drupal\Component\Attribute\AttributeString;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Random;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Attribute\AttributeCollection
 * @group Attribute
 */
class AttributeTest extends TestCase {

  /**
   * Tests the constructor of the attribute class.
   */
  public function testConstructor() {
    $attributes = new AttributeCollection(['class' => ['example-class']]);
    $this->assertTrue(isset($attributes['class']));
    $this->assertEquals(new AttributeArray('class', ['example-class']), $attributes['class']);

    // Test adding boolean attributes through the constructor.
    $attributes = new AttributeCollection(['selected' => TRUE, 'checked' => FALSE]);
    $this->assertTrue($attributes['selected']->value());
    $this->assertFalse($attributes['checked']->value());

    // Test that non-array values with name "class" are cast to array.
    $attributes = new AttributeCollection(['class' => 'example-class']);
    $this->assertTrue(isset($attributes['class']));
    $this->assertEquals(new AttributeArray('class', ['example-class']), $attributes['class']);

    // Test that safe string objects work correctly.
    $safe_string = $this->prophesize(MarkupInterface::class);
    $safe_string->__toString()->willReturn('example-class');
    $attributes = new AttributeCollection(['class' => $safe_string->reveal()]);
    $this->assertTrue(isset($attributes['class']));
    $this->assertEquals(new AttributeArray('class', ['example-class']), $attributes['class']);
  }

  /**
   * Tests set of values.
   */
  public function testSet() {
    $attributes = new AttributeCollection();
    $attributes['class'] = ['example-class'];

    $this->assertTrue(isset($attributes['class']));
    $this->assertEquals(new AttributeArray('class', ['example-class']), $attributes['class']);
  }

  /**
   * Tests adding new values to an existing part of the attribute.
   */
  public function testAdd() {
    $attributes = new AttributeCollection(['class' => ['example-class']]);

    $attributes['class'][] = 'other-class';
    $this->assertEquals(new AttributeArray('class', ['example-class', 'other-class']), $attributes['class']);
  }

  /**
   * Tests removing of values.
   */
  public function testRemove() {
    $attributes = new AttributeCollection(['class' => ['example-class']]);
    unset($attributes['class']);
    $this->assertFalse(isset($attributes['class']));
  }

  /**
   * Tests setting attributes.
   *
   * @covers ::setAttribute
   */
  public function testSetAttribute() {
    $attributes = new AttributeCollection();

    // Test adding various attributes.
    $values = ['alt', 'id', 'src', 'title', 'value'];
    foreach ($values as $key) {
      foreach (['kitten', ''] as $value) {
        $attributes = new AttributeCollection();
        $attributes->setAttribute($key, $value);
        $this->assertEquals($value, $attributes[$key]);
      }
    }

    // Test adding array to class.
    $attributes = new AttributeCollection();
    $attributes->setAttribute('class', ['kitten', 'cat']);
    $this->assertEquals(['kitten', 'cat'], $attributes['class']->value());

    // Test adding boolean attributes.
    $attributes = new AttributeCollection();
    $attributes['checked'] = TRUE;
    $this->assertTrue($attributes['checked']->value());
  }

  /**
   * Tests removing attributes.
   *
   * @covers ::removeAttribute
   */
  public function testRemoveAttribute() {
    $values = [
      'alt' => 'Alternative text',
      'id' => 'bunny',
      'src' => 'zebra',
      'style' => 'color: pink;',
      'title' => 'kitten',
      'value' => 'ostrich',
      'checked' => TRUE,
    ];
    $attributes = new AttributeCollection($values);

    // Single value.
    $attributes->removeAttribute('alt');
    $this->assertEmpty($attributes['alt']);

    // Multiple values.
    $attributes->removeAttribute('id', 'src');
    $this->assertEmpty($attributes['id']);
    $this->assertEmpty($attributes['src']);

    // Single value in array.
    $attributes->removeAttribute(['style']);
    $this->assertEmpty($attributes['style']);

    // Boolean value.
    $attributes->removeAttribute('checked');
    $this->assertEmpty($attributes['checked']);

    // Multiple values in array.
    $attributes->removeAttribute(['title', 'value']);
    $this->assertEmpty((string) $attributes);

  }

  /**
   * Tests adding class attributes with the AttributeArray helper method.
   *
   * @covers ::addClass
   */
  public function testAddClasses() {
    // Add empty Attribute object with no classes.
    $attributes = new AttributeCollection();

    // Add no class on empty attribute.
    $attributes->addClass();
    $this->assertEmpty($attributes['class']);

    // Test various permutations of adding values to empty Attribute objects.
    foreach ([NULL, FALSE, '', []] as $value) {
      // Single value.
      $attributes->addClass($value);
      $this->assertEmpty((string) $attributes);

      // Multiple values.
      $attributes->addClass($value, $value);
      $this->assertEmpty((string) $attributes);

      // Single value in array.
      $attributes->addClass([$value]);
      $this->assertEmpty((string) $attributes);

      // Single value in arrays.
      $attributes->addClass([$value], [$value]);
      $this->assertEmpty((string) $attributes);
    }

    // Add one class on empty attribute.
    $attributes->addClass('banana');
    $this->assertEquals(['banana'], $attributes['class']->value());

    // Add one class.
    $attributes->addClass('aa');
    $this->assertEquals(['banana', 'aa'], $attributes['class']->value());

    // Add multiple classes.
    $attributes->addClass('xx', 'yy');
    $this->assertEquals(['banana', 'aa', 'xx', 'yy'], $attributes['class']->value());

    // Add an array of classes.
    $attributes->addClass(['red', 'green', 'blue']);
    $this->assertEquals(['banana', 'aa', 'xx', 'yy', 'red', 'green', 'blue'], $attributes['class']->value());

    // Add an array of duplicate classes.
    $attributes->addClass(['red', 'green', 'blue'], ['aa', 'aa', 'banana'], 'yy');
    $this->assertEquals('banana aa xx yy red green blue', (string) $attributes['class']);
  }

  /**
   * Tests removing class attributes with the AttributeArray helper method.
   *
   * @covers ::removeClass
   */
  public function testRemoveClasses() {
    // Add duplicate class to ensure that both duplicates are removed.
    $classes = ['example-class', 'aa', 'xx', 'yy', 'red', 'green', 'blue', 'red'];
    $attributes = new AttributeCollection(['class' => $classes]);

    // Remove one class.
    $attributes->removeClass('example-class');
    $this->assertNotContains('example-class', $attributes['class']->value());

    // Remove multiple classes.
    $attributes->removeClass('xx', 'yy');
    $this->assertNotContains(['xx', 'yy'], $attributes['class']->value());

    // Remove an array of classes.
    $attributes->removeClass(['red', 'green', 'blue']);
    $this->assertNotContains(['red', 'green', 'blue'], $attributes['class']->value());

    // Remove a class that does not exist.
    $attributes->removeClass('gg');
    $this->assertNotContains(['gg'], $attributes['class']->value());
    // Test that the array index remains sequential.
    $this->assertEquals(['aa'], $attributes['class']->value());

    $attributes->removeClass('aa');
    $this->assertEmpty((string) $attributes);
  }

  /**
   * Tests checking for class names with the Attribute method.
   *
   * @covers ::hasClass
   */
  public function testHasClass() {
    // Test an attribute without any classes.
    $attributes = new AttributeCollection();
    $this->assertFalse($attributes->hasClass('a-class-nowhere-to-be-found'));

    // Add a class to check for.
    $attributes->addClass('we-totally-have-this-class');
    // Check that this class exists.
    $this->assertTrue($attributes->hasClass('we-totally-have-this-class'));
  }

  /**
   * Tests removing class attributes with the Attribute helper methods.
   *
   * @covers ::removeClass
   * @covers ::addClass
   */
  public function testChainAddRemoveClasses() {
    $attributes = new AttributeCollection(
      ['class' => ['example-class', 'red', 'green', 'blue']]
    );

    $attributes
      ->removeClass(['red', 'green', 'pink'])
      ->addClass(['apple', 'lime', 'grapefruit'])
      ->addClass(['banana']);
    $expected = ['example-class', 'blue', 'apple', 'lime', 'grapefruit', 'banana'];
    $this->assertEquals($expected, $attributes['class']->value(), 'Attributes chained');
  }

  /**
   * Tests iterating on the values of the attribute.
   */
  public function testIterate() {
    $attributes = new AttributeCollection(['class' => ['example-class'], 'id' => 'example-id']);

    $counter = 0;
    foreach ($attributes as $key => $value) {
      if ($counter == 0) {
        $this->assertEquals('class', $key);
        $this->assertEquals(new AttributeArray('class', ['example-class']), $value);
      }
      if ($counter == 1) {
        $this->assertEquals('id', $key);
        $this->assertEquals(new AttributeString('id', 'example-id'), $value);
      }
      $counter++;
    }
  }

  /**
   * Tests printing of an attribute.
   */
  public function testPrint() {
    $attributes = new AttributeCollection(['class' => ['example-class'], 'id' => 'example-id', 'enabled' => TRUE]);

    $content = (new Random())->name(8, TRUE);
    $html = '<div' . (string) $attributes . '>' . $content . '</div>';
    $this->assertClass('example-class', $html);
    $this->assertNoClass('example-class2', $html);

    $this->assertId('example-id', $html);
    $this->assertNoId('example-id2', $html);

    $this->assertStringContainsString('enabled', $html);
  }

  /**
   * Tests attribute values.
   *
   * @covers ::createAttributeValue
   *
   * @dataProvider providerTestAttributeValues
   */
  public function testAttributeValues(array $attributes, $expected) {
    $this->assertEquals($expected, (new AttributeCollection($attributes))->__toString());
  }

  /**
   * Provides test data for testAttributeValues.
   *
   * @return array
   *   An array of test data.
   */
  public function providerTestAttributeValues() {
    $data = [];

    $string = '"> <script>alert(123)</script>"';
    $data['safe-object-xss1'] = [['title' => TestMarkup::create($string)], ' title="&quot;&gt; alert(123)&quot;"'];
    $data['non-safe-object-xss1'] = [['title' => $string], ' title="' . Html::escape($string) . '"'];
    $string = '&quot;><script>alert(123)</script>';
    $data['safe-object-xss2'] = [['title' => TestMarkup::create($string)], ' title="&quot;&gt;alert(123)"'];
    $data['non-safe-object-xss2'] = [['title' => $string], ' title="' . Html::escape($string) . '"'];

    return $data;
  }

  /**
   * Checks that the given CSS class is present in the given HTML snippet.
   *
   * @param string $class
   *   The CSS class to check.
   * @param string $html
   *   The HTML snippet to check.
   */
  protected function assertClass($class, $html) {
    $xpath = "//*[@class='$class']";
    self::assertTrue((bool) $this->getXpathResultCount($xpath, $html));
  }

  /**
   * Checks that the given CSS class is not present in the given HTML snippet.
   *
   * @param string $class
   *   The CSS class to check.
   * @param string $html
   *   The HTML snippet to check.
   */
  protected function assertNoClass($class, $html) {
    $xpath = "//*[@class='$class']";
    self::assertFalse((bool) $this->getXpathResultCount($xpath, $html));
  }

  /**
   * Checks that the given CSS ID is present in the given HTML snippet.
   *
   * @param string $id
   *   The CSS ID to check.
   * @param string $html
   *   The HTML snippet to check.
   */
  protected function assertId($id, $html) {
    $xpath = "//*[@id='$id']";
    self::assertTrue((bool) $this->getXpathResultCount($xpath, $html));
  }

  /**
   * Checks that the given CSS ID is not present in the given HTML snippet.
   *
   * @param string $id
   *   The CSS ID to check.
   * @param string $html
   *   The HTML snippet to check.
   */
  protected function assertNoId($id, $html) {
    $xpath = "//*[@id='$id']";
    self::assertFalse((bool) $this->getXpathResultCount($xpath, $html));
  }

  /**
   * Counts the occurrences of the given XPath query in a given HTML snippet.
   *
   * @param string $query
   *   The XPath query to execute.
   * @param string $html
   *   The HTML snippet to check.
   *
   * @return int
   *   The number of results that are found.
   */
  protected function getXpathResultCount($query, $html) {
    $document = new \DOMDocument();
    $document->loadHTML($html);
    $xpath = new \DOMXPath($document);

    return $xpath->query($query)->length;
  }

  /**
   * Tests the storage method.
   */
  public function testStorage() {
    $attributes = new AttributeCollection(['class' => ['example-class']]);

    $this->assertEquals(['class' => new AttributeArray('class', ['example-class'])], $attributes->storage());
  }

  /**
   * Provides tests data for testHasAttribute.
   *
   * @return array
   *   An array of test data each containing an array of attributes, the name
   *   of the attribute to check existence of, and the expected result.
   */
  public function providerTestHasAttribute() {
    return [
      [['class' => ['example-class']], 'class', TRUE],
      [[], 'class', FALSE],
      [['class' => ['example-class']], 'id', FALSE],
      [['class' => ['example-class'], 'id' => 'foo'], 'id', TRUE],
      [['id' => 'foo'], 'class', FALSE],
    ];
  }

  /**
   * @covers ::hasAttribute
   * @dataProvider providerTestHasAttribute
   */
  public function testHasAttribute(array $test_data, $test_attribute, $expected) {
    $attributes = new AttributeCollection($test_data);
    $this->assertSame($expected, $attributes->hasAttribute($test_attribute));
  }

  /**
   * Provides tests data for testMerge.
   *
   * @return array
   *   An array of test data each containing an initial Attribute object, an
   *   Attribute object or array to be merged, and the expected result.
   */
  public function providerTestMerge() {
    return [
      [new AttributeCollection([]), new AttributeCollection(['class' => ['class1']]), new AttributeCollection(['class' => ['class1']])],
      [new AttributeCollection(['class' => ['example-class']]), new AttributeCollection(['class' => ['class1']]), new AttributeCollection(['class' => ['example-class', 'class1']])],
      [new AttributeCollection(['class' => ['example-class']]), new AttributeCollection(['id' => 'foo', 'href' => 'bar']), new AttributeCollection(['class' => ['example-class'], 'id' => 'foo', 'href' => 'bar'])],
    ];
  }

  /**
   * @covers ::merge
   * @dataProvider providerTestMerge
   */
  public function testMerge($original, $merge, $expected) {
    $this->assertEquals($expected, $original->merge($merge));
  }

  /**
   * @covers ::merge
   */
  public function testMergeArgumentException() {
    $attributes = new AttributeCollection(['class' => ['example-class']]);
    $this->expectException(\TypeError::class);
    $attributes->merge('not an array');
  }

}

/**
 * Implementation of MarkupInterface to use in tests.
 */
class TestMarkup implements MarkupInterface, \Countable {

  use MarkupTrait;

}
