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
   *           ["AttributeClass", "attribute", true]
   *           ["AttributeClass", "Attribute", true]
   *           ["AttributeClass", "\\DoesNotExist", false]
   *           ["Nonexistent", "NonexistentAttribute", false]
   *           ["MultipleAttributes", "Attribute", true]
   *           ["MultipleAttributes", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\AttributeClass", true]
   *           ["MultipleAttributes", "DoesNotExist", false]
   *           ["FullyQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["Used", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["UsedAs", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["UsedAsQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["Qualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true]
   *           ["Relative", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\SubDir\\SubDirAttribute", true]
   *           ["FullyQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true]
   *           ["Used", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true]
   *           ["UsedAs", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true]
   *           ["UsedAsQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true]
   *           ["Qualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true]
   */
  public function testAttribute(string $class, string $attribute_class, bool $expected) {
    $finder = MockFileFinder::create(__DIR__ . '/Fixtures/Attribute/' . $class . '.php');
    $parser = new StaticReflectionParser('\\Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\' . $class, $finder);
    $this->assertSame($expected, $parser->hasClassAttribute($attribute_class), "'$class' has attribute that is a '$attribute_class'");
  }

}
