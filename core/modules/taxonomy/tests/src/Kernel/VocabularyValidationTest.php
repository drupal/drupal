<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\TestTools\Attribute\ShareEnvironment;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of vocabulary entities.
 */
#[Group('taxonomy')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
#[ShareEnvironment]
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
  protected function setUpEnvironment(): void {
    parent::setUpEnvironment();

    $this->entity = Vocabulary::create([
      'vid' => 'test',
      'name' => 'Test',
    ]);
    $this->entity->save();
  }

}
