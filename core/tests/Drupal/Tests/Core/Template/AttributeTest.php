<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Template\AttributeTest.
 */

namespace Drupal\Tests\Core\Template;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Template\AttributeArray;
use Drupal\Core\Template\AttributeString;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Template\Attribute
 * @group Template
 */
class AttributeTest extends UnitTestCase {

  /**
   * Tests the constructor of the attribute class.
   */
  public function testConstructor() {
    $attribute = new Attribute(array('class' => array('example-class')));
    $this->assertTrue(isset($attribute['class']));
    $this->assertEquals(new AttributeArray('class', array('example-class')), $attribute['class']);
  }

  /**
   * Tests set of values.
   */
  public function testSet() {
    $attribute = new Attribute();
    $attribute['class'] = array('example-class');

    $this->assertTrue(isset($attribute['class']));
    $this->assertEquals(new AttributeArray('class', array('example-class')), $attribute['class']);
  }

  /**
   * Tests adding new values to an existing part of the attribute.
   */
  public function testAdd() {
    $attribute = new Attribute(array('class' => array('example-class')));

    $attribute['class'][] = 'other-class';
    $this->assertEquals(new AttributeArray('class', array('example-class', 'other-class')), $attribute['class']);
  }

  /**
   * Tests removing of values.
   */
  public function testRemove() {
    $attribute = new Attribute(array('class' => array('example-class')));
    unset($attribute['class']);
    $this->assertFalse(isset($attribute['class']));
  }

  /**
   * Tests adding class attributes with the AttributeArray helper method.
   * @covers ::addClass()
   */
  public function testAddClasses() {
    // Add empty Attribute object with no classes.
    $attribute = new Attribute();

    // Add no class on empty attribute.
    $attribute->addClass();
    $this->assertEmpty($attribute['class']);

    // Test various permutations of adding values to empty Attribute objects.
    foreach (array(NULL, FALSE, '', []) as $value) {
      // Single value.
      $attribute->addClass($value);
      $this->assertEmpty((string) $attribute);

      // Multiple values.
      $attribute->addClass($value, $value);
      $this->assertEmpty((string) $attribute);

      // Single value in array.
      $attribute->addClass([$value]);
      $this->assertEmpty((string) $attribute);

      // Single value in arrays.
      $attribute->addClass([$value], [$value]);
      $this->assertEmpty((string) $attribute);
    }

    // Add one class on empty attribute.
    $attribute->addClass('banana');
    $this->assertArrayEquals(array('banana'), $attribute['class']->value());

    // Add one class.
    $attribute->addClass('aa');
    $this->assertArrayEquals(array('banana', 'aa'), $attribute['class']->value());

    // Add multiple classes.
    $attribute->addClass('xx', 'yy');
    $this->assertArrayEquals(array('banana', 'aa', 'xx', 'yy'), $attribute['class']->value());

    // Add an array of classes.
    $attribute->addClass(array('red', 'green', 'blue'));
    $this->assertArrayEquals(array('banana', 'aa', 'xx', 'yy', 'red', 'green', 'blue'), $attribute['class']->value());

    // Add an array of duplicate classes.
    $attribute->addClass(array('red', 'green', 'blue'), array('aa', 'aa', 'banana'), 'yy');
    $this->assertEquals('banana aa xx yy red green blue', (string) $attribute['class']);
  }

  /**
   * Tests removing class attributes with the AttributeArray helper method.
   * @covers ::removeClass()
   */
  public function testRemoveClasses() {
    // Add duplicate class to ensure that both duplicates are removed.
    $classes = array('example-class', 'aa', 'xx', 'yy', 'red', 'green', 'blue', 'red');
    $attribute = new Attribute(array('class' => $classes));

    // Remove one class.
    $attribute->removeClass('example-class');
    $this->assertNotContains('example-class', $attribute['class']->value());

    // Remove multiple classes.
    $attribute->removeClass('xx', 'yy');
    $this->assertNotContains(array('xx', 'yy'), $attribute['class']->value());

    // Remove an array of classes.
    $attribute->removeClass(array('red', 'green', 'blue'));
    $this->assertNotContains(array('red', 'green', 'blue'), $attribute['class']->value());

    // Remove a class that does not exist.
    $attribute->removeClass('gg');
    $this->assertNotContains(array('gg'), $attribute['class']->value());
    // Test that the array index remains sequential.
    $this->assertArrayEquals(array('aa'), $attribute['class']->value());

    $attribute->removeClass('aa');
    $this->assertEmpty((string) $attribute);
  }

  /**
   * Tests removing class attributes with the Attribute helper methods.
   * @covers ::removeClass()
   * @covers ::addClass()
   */
  public function testChainAddRemoveClasses() {
    $attribute = new Attribute(
      array('class' => array('example-class', 'red', 'green', 'blue'))
    );

    $attribute
      ->removeClass(array('red', 'green', 'pink'))
      ->addClass(array('apple', 'lime', 'grapefruit'))
      ->addClass(array('banana'));
    $expected = array('example-class', 'blue', 'apple', 'lime', 'grapefruit', 'banana');
    $this->assertArrayEquals($expected, $attribute['class']->value(), 'Attributes chained');
  }

  /**
   * Tests the twig calls to the Attribute.
   * @dataProvider providerTestAttributeClassHelpers
   *
   * @covers ::removeClass()
   * @covers ::addClass()
   */
  public function testTwigAddRemoveClasses($template, $expected, $seed_attributes = array()) {
    $loader = new \Twig_Loader_String();
    $twig = new \Twig_Environment($loader);
    $data = array('attributes' => new Attribute($seed_attributes));
    $result = $twig->render($template, $data);
    $this->assertEquals($expected, $result);
  }

  /**
   * Provides tests data for testEscaping
   *
   * @return array
   *   An array of test data each containing of a twig template string,
   *   a resulting string of classes and an optional array of attributes.
   */
  public function providerTestAttributeClassHelpers() {
    return array(
      array("{{ attributes.class }}", ''),
      array("{{ attributes.addClass('everest').class }}", 'everest'),
      array("{{ attributes.addClass(['k2', 'kangchenjunga']).class }}", 'k2 kangchenjunga'),
      array("{{ attributes.addClass('lhotse', 'makalu', 'cho-oyu').class }}", 'lhotse makalu cho-oyu'),
      array(
        "{{ attributes.addClass('nanga-parbat').class }}",
        'dhaulagiri manaslu nanga-parbat',
        array('class' => array('dhaulagiri', 'manaslu')),
      ),
      array(
        "{{ attributes.removeClass('annapurna').class }}",
        'gasherbrum-i',
        array('class' => array('annapurna', 'gasherbrum-i')),
      ),
      array(
        "{{ attributes.removeClass(['broad peak']).class }}",
        'gasherbrum-ii',
        array('class' => array('broad peak', 'gasherbrum-ii')),
      ),
      array(
        "{{ attributes.removeClass('gyachung-kang', 'shishapangma').class }}",
        '',
        array('class' => array('shishapangma', 'gyachung-kang')),
      ),
      array(
        "{{ attributes.removeClass('nuptse').addClass('annapurna-ii').class }}",
        'himalchuli annapurna-ii',
        array('class' => array('himalchuli', 'nuptse')),
      ),
      // Test for the removal of an empty class name.
      array("{{ attributes.addClass('rakaposhi', '').class }}", 'rakaposhi'),
    );
  }

  /**
   * Tests iterating on the values of the attribute.
   */
  public function testIterate() {
    $attribute = new Attribute(array('class' => array('example-class'), 'id' => 'example-id'));

    $counter = 0;
    foreach ($attribute as $key => $value) {
      if ($counter == 0) {
        $this->assertEquals('class', $key);
        $this->assertEquals(new AttributeArray('class', array('example-class')), $value);
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
    $attribute = new Attribute(array('class' => array('example-class'), 'id' => 'example-id', 'enabled' => TRUE));

    $content = $this->randomMachineName();
    $html = '<div' . (string) $attribute . '>' . $content . '</div>';
    $this->assertSelectEquals('div.example-class', $content, 1, $html);
    $this->assertSelectEquals('div.example-class2', $content, 0, $html);

    $this->assertSelectEquals('div#example-id', $content, 1, $html);
    $this->assertSelectEquals('div#example-id2', $content, 0, $html);

    $this->assertTrue(strpos($html, 'enabled') !== FALSE);
  }

  /**
   * Tests the storage method.
   */
  public function testStorage() {
    $attribute = new Attribute(array('class' => array('example-class')));

    $this->assertEquals(array('class' => new AttributeArray('class', array('example-class'))), $attribute->storage());
  }

}
