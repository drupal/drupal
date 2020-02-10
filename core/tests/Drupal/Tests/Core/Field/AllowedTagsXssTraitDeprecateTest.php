<?php

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Tests\UnitTestCase;

/**
 * Tests AllowedTagsXssTrait.
 *
 * @group field
 * @group legacy
 */
class AllowedTagsXssTraitDeprecateTest extends UnitTestCase {

  /**
   * @expectedDeprecation Drupal\Core\Field\AllowedTagsXssTrait::fieldFilterXss is deprecated in drupal:8.0.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Field\FieldFilteredMarkup::create() instead.
   * @expectedDeprecation Drupal\Core\Field\AllowedTagsXssTrait::allowedTags is deprecated in drupal:8.0.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Field\FieldFilteredMarkup::allowedTags() instead.
   * @expectedDeprecation Drupal\Core\Field\AllowedTagsXssTrait::displayAllowedTags is deprecated in drupal:8.0.0 and is removed in drupal:9.0.0. Use \Drupal\Core\Field\FieldFilteredMarkup::displayAllowedTags() instead.
   */
  public function testDeprecation() {
    $deprecated = new FieldDeprecateAllowedTagsXssTraitClass();
    $this->assertSame('Test string', (string) $deprecated->fieldFilterXss('<object>Test string</object>'));
    $this->assertSame(FieldFilteredMarkup::allowedTags(), $deprecated->allowedTags());
    $this->assertSame(FieldFilteredMarkup::displayAllowedTags(), $deprecated->displayAllowedTags());
  }

}

/**
 * Class FieldDeprecateAllowedTagsXssTraitClass
 */
class FieldDeprecateAllowedTagsXssTraitClass {
  use AllowedTagsXssTrait;

}
