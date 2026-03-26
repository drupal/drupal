<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the EntityFieldValueTrait.
 */
#[Group('entity_api')]
#[RunTestsInSeparateProcesses]
class EntityFieldValueTraitTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Calls protected getFieldValue() method via reflection.
   */
  private function getFieldValue(User $account, string $field_name, string $property, int $delta = 0): mixed {
    $reflection = new \ReflectionClass($account);
    return $reflection->getMethod('getFieldValue')->invoke($account, $field_name, $property, $delta);
  }

  /**
   * Tests getFieldValue() via User entity methods.
   */
  public function testGetFieldValueViaUserMethods(): void {
    $account = User::create([
      'name' => 'user1',
      'mail' => 'test@example.com',
      'status' => 1,
      'timezone' => 'UTC',
    ]);
    $account->save();
    $account = User::load($account->id());
    assert($account instanceof User);

    $this->assertTrue($account->isActive());
    $this->assertSame('user1', $account->getAccountName());
    $this->assertSame('UTC', $account->getTimeZone());
    $this->assertIsNumeric($account->getLastAccessedTime());
  }

  /**
   * Tests getFieldValue() returns correct values.
   */
  public function testGetFieldValueCorrectness(): void {
    $account = User::create([
      'name' => 'user1',
      'mail' => 'test@example.com',
      'status' => 0,
    ]);
    $account->save();
    $account = User::load($account->id());
    assert($account instanceof User);

    $this->assertSame('user1', $this->getFieldValue($account, 'name', 'value'));
    $this->assertSame('test@example.com', $this->getFieldValue($account, 'mail', 'value'));
    $this->assertEquals(0, $this->getFieldValue($account, 'status', 'value'));
  }

  /**
   * Tests getFieldValue() without typed data initialization.
   */
  public function testGetFieldValueWithoutTypedDataInitialization(): void {
    $account = User::create(['name' => 'user1']);

    $this->assertSame('user1', $this->getFieldValue($account, 'name', 'value'));
    $this->assertSame('', $this->getFieldValue($account, 'mail', 'value'));
  }

  /**
   * Tests getFieldValue() falls back to initialized field objects.
   */
  public function testGetFieldValueFallsBackToInitializedFieldObjects(): void {
    $account = User::create([
      'name' => 'user1',
      'mail' => 'before@example.com',
    ]);

    $account->get('name')->value = 'user2';
    $account->get('mail')->value = 'after@example.com';
    $account->get('status')->value = 0;
    $account->get('timezone')->value = 'UTC';

    $this->assertSame('user2', $this->getFieldValue($account, 'name', 'value'));
    $this->assertSame('after@example.com', $this->getFieldValue($account, 'mail', 'value'));
    $this->assertEquals(0, $this->getFieldValue($account, 'status', 'value'));
    $this->assertSame('UTC', $this->getFieldValue($account, 'timezone', 'value'));
  }

  /**
   * Tests getFieldValue() with delta parameter.
   */
  public function testGetFieldValueWithDelta(): void {
    $account = User::create(['name' => 'user1']);
    $account->save();

    $this->assertSame('user1', $this->getFieldValue($account, 'name', 'value', 0));
    $this->assertNull($this->getFieldValue($account, 'name', 'value', 1));
  }

  /**
   * Tests getFieldValue() consistency with traditional access.
   */
  public function testGetFieldValueConsistencyWithTraditionalAccess(): void {
    $account = User::create([
      'name' => 'user1',
      'mail' => 'consistency@example.com',
      'status' => 1,
    ]);
    $account->save();
    $account = User::load($account->id());
    assert($account instanceof User);

    $this->assertSame($account->get('name')->value, $this->getFieldValue($account, 'name', 'value'));
    $this->assertSame($account->get('mail')->value, $this->getFieldValue($account, 'mail', 'value'));
    $this->assertSame($account->get('status')->value, $this->getFieldValue($account, 'status', 'value'));
  }

  /**
   * Tests getFieldValue() with scalar values.
   */
  public function testGetFieldValueWithScalarValues(): void {
    $account = User::create([
      'name' => 'user1',
      'status' => 0,
    ]);
    $account->save();
    $account = User::load($account->id());
    assert($account instanceof User);

    // Zero can be stored as '0' or 0 - use loose comparison.
    $this->assertEquals(0, $this->getFieldValue($account, 'status', 'value'));
    $this->assertSame('user1', $this->getFieldValue($account, 'name', 'value'));
  }

  /**
   * Tests getFieldValue() with non-existent field.
   */
  public function testGetFieldValueWithNonExistentField(): void {
    $account = User::create(['name' => 'user1']);
    $account->save();
    $account = User::load($account->id());
    assert($account instanceof User);

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Field nonexistent_field is unknown.');
    $this->getFieldValue($account, 'nonexistent_field', 'value');
  }

  /**
   * Tests memory reclamation after field access.
   *
   * Verifies that loading entities one at a time with proper cache reset does
   * not leak memory. Uses a warmup phase to stabilize one-time allocations,
   * then measures growth over a second batch of entities.
   *
   * Regression test for memory leaks described in:
   * - https://www.drupal.org/project/drupal/issues/3572625
   * - https://www.drupal.org/project/drupal/issues/3573982
   */
  public function testMemoryReclamationAfterFieldAccess(): void {
    $total = 200;
    for ($i = 0; $i < $total; $i++) {
      User::create([
        'name' => "user$i",
        'mail' => "user$i@example.com",
        'status' => 1,
      ])->save();
    }

    $storage = \Drupal::entityTypeManager()->getStorage('user');
    $uids = array_values($storage->getQuery()->accessCheck(FALSE)->execute());

    // Warmup: process first batch to stabilize one-time allocations
    // (autoloader, internal caches, buffers).
    $warmup_count = 50;
    for ($i = 0; $i < $warmup_count && $i < count($uids); $i++) {
      $account = $storage->load($uids[$i]);
      $this->getFieldValue($account, 'name', 'value');
      $storage->resetCache([$uids[$i]]);
    }
    gc_collect_cycles();
    $memory_after_warmup = memory_get_usage();

    // Process remaining entities and measure growth.
    for ($i = $warmup_count; $i < count($uids); $i++) {
      $account = $storage->load($uids[$i]);
      $this->getFieldValue($account, 'name', 'value');
      $storage->resetCache([$uids[$i]]);
    }
    gc_collect_cycles();
    $memory_after_all = memory_get_usage();

    // With proper cleanup, processing 150 additional entities should not
    // grow memory significantly. A leak (e.g. retained FieldItemList
    // references) would show ~2-5KB per entity, easily exceeding 100KB.
    $this->assertLessThan(100 * 1024, $memory_after_all - $memory_after_warmup);
  }

}
