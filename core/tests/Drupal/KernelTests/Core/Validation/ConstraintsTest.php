<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests various low level constrains provided by core.
 *
 * @group Validation
 */
class ConstraintsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('config_test');
  }

  /**
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\UuidConstraint
   */
  public function testUuid(): void {
    $typed_config_manager = \Drupal::service('config.typed');
    /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $typed_config */
    $typed_config = $typed_config_manager->get('config_test.validation');
    $typed_config->get('uuid')
      ->setValue(\Drupal::service('uuid')->generate());

    $this->assertCount(0, $typed_config->validate());

    $typed_config->get('uuid')
      ->setValue(\Drupal::service('uuid')->generate() . '-invalid');
    $this->assertCount(1, $typed_config->validate());
  }

}
