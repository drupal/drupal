<?php

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\Plugin\migrate\field\d7\EntityReference;
use Drupal\Core\Field\Plugin\migrate\field\d7\NumberField;
use Drupal\Core\Field\Plugin\migrate\field\Email;
use Drupal\Tests\UnitTestCase;

/**
 * Test trigger_error is fired when deprecated classes are instantiated.
 *
 * @group Field
 * @group legacy
 */
class MigrateFieldDeprecationTest extends UnitTestCase {

  /**
   * Tests trigger_error when an Email object is created.
   *
   * @expectedDeprecation Drupal\Core\Field\Plugin\migrate\field\Email is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\field\Plugin\migrate\field\Email instead. See https://www.drupal.org/node/3009286
   */
  public function testDeprecatedEmail() {
    new Email([], 'email', []);
  }

  /**
   * Tests trigger_error when an EntityReference object is created.
   *
   * @expectedDeprecation Drupal\Core\Field\Plugin\migrate\field\d7\EntityReference is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\field\Plugin\migrate\field\d7\EntityReference instead. See https://www.drupal.org/node/3009286
   */
  public function testDeprecatedEntityReference() {
    new EntityReference([], 'entityreference', []);
  }

  /**
   * Tests trigger_error when a NumberField object is created.
   *
   * @expectedDeprecation Drupal\Core\Field\Plugin\migrate\field\d7\NumberField is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\field\Plugin\migrate\field\d7\NumberField instead. See https://www.drupal.org/node/3009286
   */
  public function testDeprecatedNumberField() {
    new NumberField([], 'number_default', []);
  }

}
