<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\Validation\Plugin\Validation\Constraint\MappingCollectionConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\MappingCollectionConstraintValidator;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests nested composite validation constraints.
 */
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
#[CoversClass(MappingCollectionConstraint::class)]
#[CoversClass(MappingCollectionConstraintValidator::class)]
class MappingCollectionConstrainTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * Tests use of AtLeastOneOf validation constraint in config.
   */
  public function testConfigValidation(): void {
    $this->installConfig('config_test');

    $config = \Drupal::configFactory()->getEditable('config_test.validation');
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');

    // Should fail if neither block_id and block_revision_id, or block_serialized must be provided
    $config->set('block_id', NULL);
    $config->set('block_revision_id', NULL);
    $config->set('block_serialized', NULL);
    $config_updated = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get());
    $result = $config_updated->validate();
    $this->assertCount(1, $result);

    // Should pass if block_id and block_revision_id are provided, even though block_serialized is missing
    $config->set('block_id', 1);
    $config->set('block_revision_id', 1);
    $config->clear('block_serialized');
    $config_updated = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get());
    $result = $config_updated->validate();
    $this->assertCount(0, $result);

    // Should pass if block_serialized is provided
    $config->clear('block_id');
    $config->clear('block_revision_id');
    $config->set('block_serialized', 'a:1:{s:3:"foo";s:3:"bar";}');
    $config_updated = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get());
    $result = $config_updated->validate();
    $this->assertCount(0, $result);

    // Should fail if neither block_id and block_revision_id, or block_serialized must be provided
    $config->clear('block_id');
    $config->clear('block_revision_id');
    $config->clear('block_serialized');
    $config_updated = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get());
    $result = $config_updated->validate();
    $this->assertCount(1, $result);

    // Should fail if block_id and block_serialized are not provided
    $config->clear('block_id');
    $config->set('block_revision_id', 1);
    $config->clear('block_serialized');
    $config_updated = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get());
    $result = $config_updated->validate();
    $this->assertCount(1, $result);

    // Should fail if only block_id is provided
    $config->set('block_id', 1);
    $config->clear('block_revision_id');
    $config->clear('block_serialized');
    $config_updated = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get());
    $result = $config_updated->validate();
    $this->assertCount(1, $result);

  }

}
