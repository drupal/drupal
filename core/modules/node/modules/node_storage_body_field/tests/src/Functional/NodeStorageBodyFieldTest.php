<?php

declare(strict_types=1);

namespace Drupal\Tests\node_storage_body_field\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the node_storage_body_field deprecated module.
 */
#[Group('node_storage_body_field')]
#[RunTestsInSeparateProcesses]
class NodeStorageBodyFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'text'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the BC coverage for node_storage_body_field.
   */
  public function testBackwardsCompatibility(): void {
    // Verify storage does not exist.
    $this->assertNull(FieldStorageConfig::load('node.body'));

    // Now install the BC module.
    \Drupal::service('module_installer')->install(['node_storage_body_field_test']);

    // Verify storage exists.
    $this->assertNotNull(FieldStorageConfig::load('node.body'));

    // Verify field exists.
    $this->assertNotNull(FieldConfig::load('node.article.body'));

    // Now uninstall node_storage_body_field_test.
    $this->container->get('module_installer')->uninstall(['node_storage_body_field_test']);

    // Verify again storage exists.
    $this->assertNotNull(FieldStorageConfig::load('node.body'));

    // Verify again field exists.
    $this->assertNotNull(FieldConfig::load('node.article.body'));
  }

}
