<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Datetime\Entity\DateFormat;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests a subclass that does not share its environment.
 *
 * This class carries no ShareEnvironment attribute on purpose, and it assigns
 * ::$entity from ::setUp() after the parent method ran. Subclasses wrote their
 * fixture that way before the shared environment existed, and contrib
 * subclasses still do. Every test method of the base class runs here, against a
 * fixture built once per test method.
 *
 * @see \Drupal\TestTools\Attribute\ShareEnvironment
 */
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class ConfigEntityValidationTestBaseUnsharedTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = DateFormat::create([
      'id' => 'test',
      'label' => 'Test',
      'pattern' => 'Y-m-d',
    ]);
    $this->entity->save();
  }

  /**
   * Tests that the entity assigned after ::setUp() is the one under test.
   *
   * The base class reloads ::$entity only for a shared environment. Reloading
   * it here would throw, because nothing recorded which entity to load.
   */
  public function testEntityComesFromSetUp(): void {
    $this->assertFalse(static::sharesEnvironment());
    $this->assertSame('test', $this->entity->id());
    $this->assertFalse($this->entity->isNew());
  }

}
