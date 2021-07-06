<?php

namespace Drupal\Tests\Core\Template;

use Drupal\Component\Attribute\AttributeArray;
use Drupal\Component\Attribute\AttributeBoolean;
use Drupal\Component\Attribute\AttributeString;
use Drupal\Component\Attribute\AttributeValueBase;
use Drupal\Core\Template\AttributeArray as CoreAttributeArray;
use Drupal\Core\Template\AttributeBoolean as CoreAttributeBoolean;
use Drupal\Core\Template\AttributeString as CoreAttributeString;
use Drupal\Core\Template\AttributeValueBase as CoreAttributeValueBase;
use Drupal\Tests\UnitTestCase;

/**
 * Deprecation tests for the core Attribute* classes.
 *
 * @group Template
 * @group legacy
 */
class AttributeLegacyTest extends UnitTestCase {

  /**
   * Tests deprecation of Attribute* classes.
   */
  public function testCoreAttributeDeprecations(): void {
    $this->expectDeprecation('\Drupal\Core\Template\AttributeArray is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeArray instead. See https://www.drupal.org/node/3070485');
    $this->expectDeprecation('\Drupal\Core\Template\AttributeBoolean is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeBoolean instead. See https://www.drupal.org/node/3070485');
    $this->expectDeprecation('\Drupal\Core\Template\AttributeString is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeString instead. See https://www.drupal.org/node/3070485');
    $this->assertInstanceOf(AttributeArray::class, new CoreAttributeArray('a', ['test']));
    $this->assertInstanceOf(AttributeBoolean::class, new CoreAttributeBoolean('b', FALSE));
    $this->assertInstanceOf(AttributeString::class, new CoreAttributeString('c', 'test'));
  }

  /**
   * Tests deprecation of AttributeValueBase.
   */
  public function testCoreAttributeValueBaseDeprecation(): void {
    $this->expectDeprecation('\Drupal\Core\Template\AttributeValueBase is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\Component\Attribute\AttributeValueBase instead. See https://www.drupal.org/node/3070485');
    $this->assertInstanceOf(AttributeValueBase::class, new TestAttributeValueBase('a', ['test']));
  }

}

/**
 * Test class for Drupal\Core\Template\AttributeValueBase.
 */
class TestAttributeValueBase extends CoreAttributeValueBase {

  /**
   * Implements the magic __toString() method.
   */
  public function __toString(): string {
    return '';
  }

}
