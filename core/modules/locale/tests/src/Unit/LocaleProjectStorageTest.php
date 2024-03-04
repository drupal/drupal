<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Unit;

use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\locale\LocaleProjectStorage;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\locale\LocaleProjectStorage
 * @group locale
 * @runTestsInSeparateProcesses
 */
class LocaleProjectStorageTest extends UnitTestCase {

  /**
   * @var \Drupal\locale\LocaleProjectStorage
   */
  private LocaleProjectStorage $projectStorage;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueMemoryFactory
   */
  private KeyValueMemoryFactory $keyValueMemoryFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->keyValueMemoryFactory = new KeyValueMemoryFactory();
    $this->projectStorage = new LocaleProjectStorage($this->keyValueMemoryFactory);
  }

  /**
   * Tests that projects are sorted by weight and key.
   */
  public function testSorting(): void {
    // There are no projects.
    $this->assertSame([], $this->projectStorage->getAll());

    // Add project 'b'.
    $this->projectStorage->set('b', ['name' => 'b']);
    $this->assertSame(['b'], array_keys($this->projectStorage->getAll()));

    // Add project 'c' and confirm alphabetical order.
    $this->projectStorage->set('c', ['name' => 'c']);
    $this->assertSame(['b', 'c'], array_keys($this->projectStorage->getAll()));

    // Add project 'a' and confirm 'a' is first.
    $this->projectStorage->set('a', ['name' => 'a']);
    $this->assertSame(['a', 'b', 'c'], array_keys($this->projectStorage->getAll()));

    // Add project 'd' with a negative weight and confirm 'd' is first.
    $this->projectStorage->set('d', ['name' => 'd', 'weight' => -1]);
    $this->assertSame(['d', 'a', 'b', 'c'], array_keys($this->projectStorage->getAll()));

    // Add project 'aa' with a positive weight and confirm 'aa' is last.
    $this->projectStorage->set('aa', ['name' => 'aa', 'weight' => 1]);
    $this->assertSame(['d', 'a', 'b', 'c', 'aa'], array_keys($this->projectStorage->getAll()));

    // Delete project 'a'.
    $this->projectStorage->delete('a');
    $this->assertSame(['d', 'b', 'c', 'aa'], array_keys($this->projectStorage->getAll()));

    // Add project 'e' with a lower negative weight than 'd' and confirm 'e' is
    // first.
    $this->projectStorage->set('e', ['name' => 'e', 'weight' => -5]);
    $this->assertSame(['e', 'd', 'b', 'c', 'aa'], array_keys($this->projectStorage->getAll()));

    // Pretend there is a container rebuild by generating a new
    // LocaleProjectStorage object with the same data.
    $this->projectStorage = new LocaleProjectStorage($this->keyValueMemoryFactory);
    $this->projectStorage->set('z', ['name' => 'z']);
    $this->assertSame(['e', 'd', 'b', 'c', 'z', 'aa'], array_keys($this->projectStorage->getAll()));

    // Now delete all projects.
    $this->projectStorage->deleteAll();
    $this->assertSame([], $this->projectStorage->getAll());

    // Add project 'z' before project 'a' and confirm 'a' is first.
    $this->projectStorage->set('z', ['name' => 'z']);
    $this->projectStorage->set('a', ['name' => 'a']);
    $this->assertSame(['a', 'z'], array_keys($this->projectStorage->getAll()));
  }

  /**
   * Tests deleted projects are not included in the count.
   */
  public function testDelete(): void {
    $this->projectStorage->set('b', ['name' => 'b']);
    $this->assertSame(['name' => 'b'], $this->projectStorage->get('b'));
    $this->assertSame(1, $this->projectStorage->countProjects());
    $this->projectStorage->delete('b');
    $this->assertNull($this->projectStorage->get('b'));
    $this->assertSame(0, $this->projectStorage->countProjects());
  }

}
