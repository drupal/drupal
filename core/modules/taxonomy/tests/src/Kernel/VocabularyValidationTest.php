<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of vocabulary entities.
 */
#[Group('taxonomy')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class VocabularyValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static array $propertiesWithOptionalValues = ['description'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = Vocabulary::create([
      'vid' => 'test',
      'name' => 'Test',
    ]);
    $this->entity->save();
  }

}
