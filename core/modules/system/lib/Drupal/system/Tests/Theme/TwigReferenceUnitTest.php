<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\TwigReferenceUnitTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\UnitTestBase;
use Drupal\Core\Template\TwigReference;
use Drupal\Core\Template\TwigReferenceFunctions;

/**
 * Unit tests for TwigReference class.
 */
class TwigReferenceUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Theme Twig References',
      'description' => 'Tests TwigReference functions',
      'group' => 'Theme',
    );
  }

  function setUp() {
    parent::setUp();
    $this->variables = array(
      'foo' => 'bar',
      'baz' => array(
        'foo' => '42',
        'bar' => '23',
      ),
      'node' => new TwigReferenceObjectTest(
        42,
        'test node'
      )
    );
  }

  /**
   * Test function for TwigReference class
   */
  function testTwigReference() {
    // Create a new TwigReference wrapper
    $wrapper = new TwigReference();
    $wrapper->setReference($this->variables);

    // Check that strings are returned as strings
    $foo = $wrapper['foo'];
    $this->assertEqual($foo, $this->variables['foo'], 'String returned from TwigReference is the same');
    $this->assertTrue(is_string($foo), 'String returned from TwigReference is of type string');

    // Check that arrays are wrapped again as TwigReference objects
    $baz = $wrapper['baz'];
    $this->assertTrue(is_object($baz), 'Array returned from TwigReference is of type object');
    $this->assertTrue($baz instanceof TwigReference, 'Array returned from TwigReference is instance of TwigReference');

    // Check that getReference is giving back a reference to the original array

    $ref = &$baz->getReference();
    $this->assertTrue(is_array($ref), 'getReference returns an array');

    // Now modify $ref
    $ref['#hidden'] = TRUE;
    $this->assertEqual($ref['#hidden'], $this->variables['baz']['#hidden'], 'Property set on reference is passed to original array.');
    $this->assertEqual($ref['#hidden'], $baz['#hidden'], 'Property set on reference is passed to wrapper.');

    // Now modify $baz
    $baz['hi'] = 'hello';

    $this->assertEqual($baz['hi'], $this->variables['baz']['hi'], 'Property set on TwigReference object is passed to original array.');
    $this->assertEqual($baz['hi'], $ref['hi'], 'Property set on TwigReference object is passed to reference.');

    // Check that an object is passed through directly
    $node = $wrapper['node'];
    $this->assertTrue(is_object($node), 'Object returned from TwigReference is of type object');
    $this->assertTrue($node instanceof TwigReferenceObjectTest, 'Object returned from TwigReference is instance of TwigReferenceObjectTest');
  }

  /**
   * Test function for TwigReferenceFunctions class
   */
  function testTwigReferenceFunctions() {

    // Create wrapper
    $content = &$this->variables;

    // Use twig nomenclature
    $context['content'] = $content;

    // Twig converts {{ hide(content.baz) }} to the following code

    // This will have failed, because getAttribute returns a value and not a reference

    try {
      if (isset($context["content"])) {
        $_content_ = $context["content"];
      }
      else {
        $_content_ = NULL;
      }
      TwigReferenceFunctions::hide($this->getAttribute($_content_, "baz"));
    }
    catch (Exception $e) {
      // Catch the critical warning that a value was passed by reference
    }
    $this->assertFalse(isset($content['baz']['#printed']), 'baz is not hidden in content after hide() via value');

    // Now lets do the same with some TwigReference magic!

    $content_wrapper = new TwigReference();
    $content_wrapper->setReference($content);
    $context['content'] = $content_wrapper;

    // Twig converts {{ hide(content.baz) }} to the following code

    // This will succeed, because getAttribute returns a value, but it is an object

    if (isset($context["content"])) {
      $_content_ = $context["content"];
    }
    else {
      $_content_ = NULL;
    }
    TwigReferenceFunctions::hide($this->getAttribute($_content_, "baz"));

    $this->assertTrue(isset($content['baz']['#printed']), 'baz is hidden in content after hide() via TwigReference object');

    $type = TwigReferenceFunctions::gettype($this->getAttribute($_content_, "baz"));
    $this->assertEqual($type, 'array', 'Type returned via TwigReferenceFunctions:: is an array.');

    $type = gettype($this->getAttribute($_content_, "baz"));
    $this->assertEqual($type, 'object', 'Type returned without TwigReferenceFunctions:: is an object.');
  }

  /**
   *  Helper function to somehow simulate Twigs getAttribute function
   */
  public function getAttribute($array, $offset) {
    if (isset($array[$offset])) {
      return $array[$offset];
    }

    return NULL;
  }
}
