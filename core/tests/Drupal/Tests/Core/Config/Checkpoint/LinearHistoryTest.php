<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Checkpoint;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Checkpoint\Checkpoint;
use Drupal\Core\Config\Checkpoint\CheckpointExistsException;
use Drupal\Core\Config\Checkpoint\UnknownCheckpointException;
use Drupal\Core\Config\Checkpoint\LinearHistory;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Config\Checkpoint\LinearHistory
 * @group Config
 */
class LinearHistoryTest extends UnitTestCase {

  /**
   * The key used store of all the checkpoint names in state.
   *
   * @see \Drupal\Core\Config\Checkpoint\Checkpoints::CHECKPOINT_KEY
   */
  private const CHECKPOINT_KEY = 'config.checkpoints';

  /**
   * @covers ::add
   * @covers ::count
   * @covers ::getActiveCheckpoint
   * @covers \Drupal\Core\Config\Checkpoint\Checkpoint
   */
  public function testAdd(): void {
    $state = $this->prophesize(StateInterface::class);
    $state->get(self::CHECKPOINT_KEY, [])->willReturn([]);
    $state->set(self::CHECKPOINT_KEY, Argument::any())->willReturn(NULL);
    $time = $this->prophesize(TimeInterface::class);
    $time->getCurrentTime()->willReturn(1701539520, 1701539994);
    $checkpoints = new LinearHistory($state->reveal(), $time->reveal());

    $this->assertCount(0, $checkpoints);
    $this->assertNull($checkpoints->getActiveCheckpoint());

    $checkpoint = $checkpoints->add('hash1', 'Label');

    $this->assertSame('hash1', $checkpoint->id);
    $this->assertSame('Label', $checkpoint->label);
    $this->assertNull($checkpoint->parent);
    $this->assertSame(1701539520, $checkpoint->timestamp);

    $this->assertCount(1, $checkpoints);
    $this->assertSame('hash1', $checkpoints->getActiveCheckpoint()?->id);

    // Test that on the second call to add the ancestor is set correctly.
    $checkpoint2 = $checkpoints->add('hash2', new FormattableMarkup('Another label', []));
    $this->assertSame('hash2', $checkpoint2->id);
    $this->assertSame('Another label', (string) $checkpoint2->label);
    $this->assertSame($checkpoint->id, $checkpoint2->parent);
    $this->assertSame(1701539994, $checkpoint2->timestamp);

    $this->assertCount(2, $checkpoints);
    $this->assertSame('hash2', $checkpoints->getActiveCheckpoint()?->id);

    // Test that the checkpoints object can be iterated over.
    $i = 0;
    foreach ($checkpoints as $value) {
      $i++;
      $this->assertInstanceOf(Checkpoint::class, $value);
      $this->assertSame('hash' . $i, $value->id);
    }
  }

  /**
   * @covers ::add
   */
  public function testAddException(): void {
    $state = $this->prophesize(StateInterface::class);
    $state->get(self::CHECKPOINT_KEY, [])->willReturn([]);
    $state->set(self::CHECKPOINT_KEY, Argument::any())->willReturn(NULL);
    $time = $this->prophesize(TimeInterface::class);
    $time->getCurrentTime()->willReturn(1701539520, 1701539994);
    $checkpoints = new LinearHistory($state->reveal(), $time->reveal());
    $checkpoints->add('hash1', 'Label');
    // Add another checkpoint with the same ID and an exception should be
    // triggered.
    $this->expectException(CheckpointExistsException::class);
    $this->expectExceptionMessage('Cannot create a checkpoint with the ID "hash1" as it already exists');
    $checkpoints->add('hash1', 'Label');
  }

  /**
   * @covers ::delete
   */
  public function testDeleteAll(): void {
    $state = $this->prophesize(StateInterface::class);
    $state->get(self::CHECKPOINT_KEY, [])->willReturn([
      'hash1' => new Checkpoint('hash1', 'One', 1701539510, NULL),
      'hash2' => new Checkpoint('hash2', 'Two', 1701539520, 'hash1'),
      'hash3' => new Checkpoint('hash3', 'Three', 1701539530, 'hash2'),
    ]);
    $state->delete(self::CHECKPOINT_KEY)->willReturn();
    $time = $this->prophesize(TimeInterface::class);
    $checkpoints = new LinearHistory($state->reveal(), $time->reveal());

    $this->assertCount(3, $checkpoints);
    $this->assertSame('hash3', $checkpoints->getActiveCheckpoint()?->id);
    $checkpoints->deleteAll();
    $this->assertCount(0, $checkpoints);
    $this->assertNull($checkpoints->getActiveCheckpoint());
  }

  /**
   * @covers ::delete
   */
  public function testDelete(): void {
    $state = $this->prophesize(StateInterface::class);
    $test_data = [
      'hash1' => new Checkpoint('hash1', 'One', 1701539510, NULL),
      'hash2' => new Checkpoint('hash2', 'Two', 1701539520, 'hash1'),
      'hash3' => new Checkpoint('hash3', 'Three', 1701539530, 'hash2'),
    ];
    $state->get(self::CHECKPOINT_KEY, [])->willReturn($test_data);
    unset($test_data['hash1'], $test_data['hash2']);
    $state->set(self::CHECKPOINT_KEY, $test_data)->willReturn();
    $time = $this->prophesize(TimeInterface::class);
    $checkpoints = new LinearHistory($state->reveal(), $time->reveal());

    $this->assertCount(3, $checkpoints);
    $this->assertSame('hash3', $checkpoints->getActiveCheckpoint()?->id);
    $checkpoints->delete('hash2');
    $this->assertCount(1, $checkpoints);
    $this->assertSame('hash3', $checkpoints->getActiveCheckpoint()?->id);
  }

  /**
   * @covers ::delete
   */
  public function testDeleteException(): void {
    $state = $this->prophesize(StateInterface::class);
    $state->get(self::CHECKPOINT_KEY, [])->willReturn([]);
    $time = $this->prophesize(TimeInterface::class);
    $checkpoints = new LinearHistory($state->reveal(), $time->reveal());

    $this->expectException(UnknownCheckpointException::class);
    $this->expectExceptionMessage('Cannot delete a checkpoint with the ID "foo" as it does not exist');

    $checkpoints->delete('foo');
  }

  /**
   * @covers ::getParents
   */
  public function testGetParents(): void {
    $state = $this->prophesize(StateInterface::class);
    $test_data = [
      'hash1' => new Checkpoint('hash1', 'One', 1701539510, NULL),
      'hash2' => new Checkpoint('hash2', 'Two', 1701539520, 'hash1'),
      'hash3' => new Checkpoint('hash3', 'Three', 1701539530, 'hash2'),
    ];
    $state->get(self::CHECKPOINT_KEY, [])->willReturn($test_data);
    $time = $this->prophesize(TimeInterface::class);
    $checkpoints = new LinearHistory($state->reveal(), $time->reveal());

    $this->assertSame(['hash2' => $test_data['hash2'], 'hash1' => $test_data['hash1']], iterator_to_array($checkpoints->getParents('hash3')));
    $this->assertSame(['hash1' => $test_data['hash1']], iterator_to_array($checkpoints->getParents('hash2')));
    $this->assertSame([], iterator_to_array($checkpoints->getParents('hash1')));
  }

  /**
   * @covers ::getParents
   */
  public function testGetParentsException(): void {
    $state = $this->prophesize(StateInterface::class);
    $test_data = [
      'hash1' => new Checkpoint('hash1', 'One', 1701539510, NULL),
      'hash2' => new Checkpoint('hash2', 'Two', 1701539520, 'hash1'),
    ];
    $state->get(self::CHECKPOINT_KEY, [])->willReturn($test_data);
    $time = $this->prophesize(TimeInterface::class);
    $checkpoints = new LinearHistory($state->reveal(), $time->reveal());

    $this->expectException(UnknownCheckpointException::class);
    $this->expectExceptionMessage('The checkpoint "hash3" does not exist');
    iterator_to_array($checkpoints->getParents('hash3'));
  }

}
