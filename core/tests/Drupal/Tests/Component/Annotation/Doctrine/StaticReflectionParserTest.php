<?php

namespace Drupal\Tests\Component\Annotation\Doctrine;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Drupal\Component\Annotation\Doctrine\StaticReflectionParser;
use Drupal\Component\Annotation\Reflection\MockFileFinder;
use PHPUnit\Framework\TestCase;

/**
 * Tests Drupal\Component\Annotation\Doctrine\StaticReflectionParser.
 */
#[CoversClass(StaticReflectionParser::class)]
#[Group('Annotation')]
class StaticReflectionParserTest extends TestCase {

  #[TestWith(["AttributeClass", "\\Attribute", true])]
  #[TestWith(["AttributeClass", "attribute", true])]
  #[TestWith(["AttributeClass", "Attribute", true])]
  #[TestWith(["AttributeClass", "\\DoesNotExist", false])]
  #[TestWith(["Nonexistent", "NonexistentAttribute", false])]
  #[TestWith(["MultipleAttributes", "Attribute", true])]
  #[TestWith(["MultipleAttributes", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\AttributeClass", true])]
  #[TestWith(["MultipleAttributes", "DoesNotExist", false])]
  #[TestWith(["FullyQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true])]
  #[TestWith(["Used", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true])]
  #[TestWith(["UsedAs", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true])]
  #[TestWith(["UsedAsQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true])]
  #[TestWith(["Qualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleAttribute", true])]
  #[TestWith(["Relative", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\SubDir\\SubDirAttribute", true])]
  #[TestWith(["FullyQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true])]
  #[TestWith(["Used", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true])]
  #[TestWith(["UsedAs", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true])]
  #[TestWith(["UsedAsQualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true])]
  #[TestWith(["Qualified", "Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\ExtraAttributes\\ExampleParentAttribute", true])]
  public function testAttribute(string $class, string $attribute_class, bool $expected): void {
    $finder = MockFileFinder::create(__DIR__ . '/Fixtures/Attribute/' . $class . '.php');
    $parser = new StaticReflectionParser('\\Drupal\\Tests\\Component\\Annotation\\Doctrine\\Fixtures\\Attribute\\' . $class, $finder);
    $this->assertSame($expected, $parser->hasClassAttribute($attribute_class), "'$class' has attribute that is a '$attribute_class'");
  }

}
