<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests fast scalar field access on the user entity.
 *
 * @see \Drupal\Core\Entity\EntityFieldValueTrait
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
class UserFieldValueTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests that fast scalar access matches typed data without field init.
   */
  public function testFastPathMatchesTypedDataOnLoadedEntity(): void {
    $saved = $this->createUserEntity();

    /** @var \Drupal\user\Entity\User $fast_user */
    $fast_user = User::load($saved->id());
    /** @var \Drupal\user\Entity\User $typed_user */
    $typed_user = User::load($saved->id());

    $this->assertSame($typed_user->get('name')->value, $fast_user->getAccountName());
    $this->assertSame($typed_user->get('timezone')->value, $fast_user->getTimeZone());
    $this->assertSame($typed_user->get('status')->value == 1, $fast_user->isActive());
    $this->assertEquals($typed_user->get('access')->value, $fast_user->getLastAccessedTime());
  }

  /**
   * Tests that the fast path falls back to initialized field objects.
   */
  public function testFastPathFallsBackToInitializedFieldObjects(): void {
    $user = User::create([
      'name' => 'initial-name',
      'mail' => 'initial@example.com',
    ]);

    $user->get('name')->value = 'updated-name';
    $user->get('status')->value = 0;
    $user->get('access')->value = 987654321;
    $user->get('timezone')->value = 'UTC';

    $this->assertSame('updated-name', $user->getAccountName());
    $this->assertFalse($user->isActive());
    $this->assertSame('UTC', $user->getTimeZone());
    $this->assertEquals($user->get('access')->value, $user->getLastAccessedTime());
  }

  /**
   * Tests that missing raw values still match the regular field API.
   */
  public function testFastPathHandlesMissingRawValues(): void {
    $fast_user = User::create([
      'name' => 'fresh-user',
      'mail' => 'fresh-user@example.com',
    ]);
    $typed_user = User::create([
      'name' => 'fresh-user',
      'mail' => 'fresh-user@example.com',
    ]);

    $this->assertSame($typed_user->get('timezone')->value, $fast_user->getTimeZone());
    $this->assertSame($typed_user->get('status')->value == 1, $fast_user->isActive());
    $this->assertEquals($typed_user->get('access')->value, $fast_user->getLastAccessedTime());
  }

  /**
   * Profiles memory usage of getFieldValue() vs typed data field access.
   *
   * Not a pass/fail test — reports measurements to help developers understand
   * the memory characteristics of each access path. Run with --display-notices
   * or check test output for the report.
   *
   * Example output (PHP 8.5, SQLite):
   *   Entity load: ~23KB, getFieldValue: ~280 bytes, get()->value: ~4KB
   *   Batch with resetCache: 0 bytes growth for both paths.
   */
  public function testMemoryProfile(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    for ($i = 0; $i < 10; $i++) {
      $this->createUserEntity(['name' => "profiler$i", 'mail' => "profiler$i@example.com"]);
    }
    $storage->resetCache();
    gc_collect_cycles();

    $uids = array_values($storage->getQuery()->accessCheck(FALSE)->execute());
    $uid = $uids[1];

    // Measure single entity.
    gc_collect_cycles();
    $before = memory_get_usage();
    $account = $storage->load($uid);
    $after_load = memory_get_usage();

    $account->getAccountName();
    $after_trait = memory_get_usage();

    $account->get('mail')->value;
    $after_typed = memory_get_usage();

    $storage->resetCache([$uid]);
    unset($account);
    gc_collect_cycles();
    $after_cleanup = memory_get_usage();

    // Measure batch — trait path.
    gc_collect_cycles();
    $b1 = memory_get_usage();
    foreach ($uids as $u) {
      $a = $storage->load($u);
      $a->getAccountName();
      $a->isActive();
      $a->getTimeZone();
      $storage->resetCache([$u]);
    }
    unset($a);
    gc_collect_cycles();
    $a1 = memory_get_usage();

    // Measure batch — typed data path.
    gc_collect_cycles();
    $b2 = memory_get_usage();
    foreach ($uids as $u) {
      $a = $storage->load($u);
      $a->get('name')->value;
      $a->get('status')->value;
      $a->get('timezone')->value;
      $storage->resetCache([$u]);
    }
    unset($a);
    gc_collect_cycles();
    $a2 = memory_get_usage();

    $report = sprintf(
      "Memory profile (PHP %s):\n" .
      "  Entity load:         %d bytes\n" .
      "  getFieldValue:       %d bytes\n" .
      "  get()->value:        %d bytes\n" .
      "  After cleanup:       %d bytes residual\n" .
      "  Batch trait (%d):    %d bytes growth\n" .
      "  Batch typed (%d):    %d bytes growth",
      PHP_VERSION,
      $after_load - $before,
      $after_trait - $after_load,
      $after_typed - $after_trait,
      $after_cleanup - $before,
      count($uids), $a1 - $b1,
      count($uids), $a2 - $b2,
    );
    // Report via notice so it shows with --display-notices.
    trigger_error($report, E_USER_NOTICE);
    $this->addToAssertionCount(1);
  }

  /**
   * Creates and saves a user entity for field-value tests.
   */
  private function createUserEntity(array $values = []): User {
    $name = $values['name'] ?? $this->randomMachineName();
    $mail = $values['mail'] ?? $name . '@example.com';
    $user = User::create($values + [
      'name' => $name,
      'mail' => $mail,
      'status' => 1,
      'access' => 123456789,
      'timezone' => 'Europe/Berlin',
    ]);
    $user->save();
    return $user;
  }

}
