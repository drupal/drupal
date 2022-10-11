<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Tests\system\Functional\Database\FakeRecord;

/**
 * Trait to manage data samples for test tables.
 */
trait DatabaseTestSchemaDataTrait {

  /**
   * Sets up our sample data.
   */
  protected function addSampleData(): void {

    // We need the IDs, so we can't use a multi-insert here.
    $john = $this->connection->insert('test')
      ->fields([
        'name' => 'John',
        'age' => 25,
        'job' => 'Singer',
      ])
      ->execute();

    $george = $this->connection->insert('test')
      ->fields([
        'name' => 'George',
        'age' => 27,
        'job' => 'Singer',
      ])
      ->execute();

    $this->connection->insert('test')
      ->fields([
        'name' => 'Ringo',
        'age' => 28,
        'job' => 'Drummer',
      ])
      ->execute();

    $paul = $this->connection->insert('test')
      ->fields([
        'name' => 'Paul',
        'age' => 26,
        'job' => 'Songwriter',
      ])
      ->execute();

    $this->connection->insert('test_classtype')
      ->fields([
        'classname' => FakeRecord::class,
        'name' => 'Kay',
        'age' => 26,
        'job' => 'Web Developer',
      ])
      ->execute();

    $this->connection->insert('test_people')
      ->fields([
        'name' => 'Meredith',
        'age' => 30,
        'job' => 'Speaker',
      ])
      ->execute();

    $this->connection->insert('test_task')
      ->fields(['pid', 'task', 'priority'])
      ->values([
        'pid' => $john,
        'task' => 'eat',
        'priority' => 3,
      ])
      ->values([
        'pid' => $john,
        'task' => 'sleep',
        'priority' => 4,
      ])
      ->values([
        'pid' => $john,
        'task' => 'code',
        'priority' => 1,
      ])
      ->values([
        'pid' => $george,
        'task' => 'sing',
        'priority' => 2,
      ])
      ->values([
        'pid' => $george,
        'task' => 'sleep',
        'priority' => 2,
      ])
      ->values([
        'pid' => $paul,
        'task' => 'found new band',
        'priority' => 1,
      ])
      ->values([
        'pid' => $paul,
        'task' => 'perform at superbowl',
        'priority' => 3,
      ])
      ->execute();

    $this->connection->insert('select')
      ->fields([
        'id' => 1,
        'update' => 'Update value 1',
      ])
      ->execute();

    $this->connection->insert('virtual')
      ->fields([
        'id' => 1,
        'function' => 'Function value 1',
      ])
      ->execute();
  }

}
