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
 * Tests the template attribute class.
 *
 * @see \Drupal\Core\Template\Attribute
 */
class AttributeTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Attribute class',
      'description' => 'Tests the template attribute class.',
      'group' => 'Template',
    );
  }

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

    $content = $this->randomName();
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
