<?php

namespace Drupal\Tests\Component\Annotation\Doctrine;

use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Annotation\Doctrine\StaticReflectionParser
 *
 * @group Annotation
 */
class StaticReflectionParserTest extends TestCase {

  /**
   * @testWith ["AttributeClass", "\\Attribute", true]
   *           ["AttributeClass", "Attribute", true]
   *           ["AttributeClass", "\\DoesNotExist", false]
   *           ["MultipleAttributes", "Attribute", true]
   *           ["MultipleAttributes", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\AttributeClass", true]
   *           ["MultipleAttributes", "DoesNotExist", false]
   *           ["FullyQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["Used", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["UsedAs", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["UsedAsQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["Qualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["Relative", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\SubDir\\SubDirAttribute", true]
   */
  public function testAttribute(string $class, string $attribute_class, bool $expected) {
    $finder = MockFileFinder::create(__DIR__ . '/Fixtures/Attribute/' . $class . '.php');
    $parser = new StaticReflectionParser('\\Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\' . $class, $finder);
    $this->assertSame($expected, $parser->hasClassAttribute($attribute_class), "'$class' has '$attribute_class'");
    // Attribute names and namespaces are case-insensitive in PHP. Practically
    // Composer autoloading makes this untrue but builtins like \Attribute are
    // case-insensitive so we should support that.
    $this->assertSame($expected, $parser->hasClassAttribute(strtoupper($attribute_class)), "'$class' has '$attribute_class'");
  }

}
