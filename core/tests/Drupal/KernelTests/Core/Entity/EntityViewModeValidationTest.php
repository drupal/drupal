<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of entity_view_mode entities.
 */
#[Group('Entity')]
#[Group('Validation')]
#[Group('config')]
#[RunTestsInSeparateProcesses]
class EntityViewModeValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('user');

    $this->entity = EntityViewMode::create([
      'id' => 'user.test',
      'label' => 'Test',
      'targetEntityType' => 'user',
    ]);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function testImmutableProperties(array $valid_values = []): void {
    $valid_values['id'] = 'user.test_changed';
    parent::testImmutableProperties($valid_values);
  }

}
