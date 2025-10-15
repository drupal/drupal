<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content_storage_body_field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the block_content_storage_body_field deprecated module.
 */
#[Group('block_content_storage_body_field')]
#[RunTestsInSeparateProcesses]
class BlockContentStorageBodyFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'text'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the BC coverage for block_content_storage_body_field.
   */
  public function testBackwardsCompatibility(): void {
    // Verify storage does not exist.
    $this->assertNull(FieldStorageConfig::load('block_content.body'));

    // Now install the BC module.
    \Drupal::service('module_installer')->install(['block_content_storage_body_field_test']);

    // Verify storage exists.
    $this->assertNotNull(FieldStorageConfig::load('block_content.body'));

    // Verify field exists.
    $this->assertNotNull(FieldConfig::load('block_content.basic.body'));

    // Now uninstall block_content_storage_body_field_test.
    $this->container->get('module_installer')->uninstall(['block_content_storage_body_field_test']);

    // Verify again storage exists.
    $this->assertNotNull(FieldStorageConfig::load('block_content.body'));

    // Verify again field exists.
    $this->assertNotNull(FieldConfig::load('block_content.basic.body'));
  }

}
