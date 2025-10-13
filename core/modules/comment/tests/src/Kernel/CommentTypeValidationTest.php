<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Kernel;

use Drupal\comment\Entity\CommentType;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of comment_type entities.
 */
#[Group('comment')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class CommentTypeValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = CommentType::create([
      'id' => 'test',
      'label' => 'Test',
      'target_entity_type_id' => 'node',
    ]);
    $this->entity->save();
  }

}
