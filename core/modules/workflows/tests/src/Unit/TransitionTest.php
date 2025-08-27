<?php

declare(strict_types=1);

namespace Drupal\Tests\workflows\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\workflow_type_test\Plugin\WorkflowType\TestType;
use Drupal\workflows\Transition;
use Drupal\workflows\WorkflowTypeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\workflows\Transition.
 */
#[CoversClass(Transition::class)]
#[Group('workflows')]
class TransitionTest extends UnitTestCase {

  /**
   * Tests getters.
   *
   * @legacy-covers ::__construct
   * @legacy-covers ::id
   * @legacy-covers ::label
   */
  public function testGetters(): void {
    $state = new Transition(
      $this->prophesize(WorkflowTypeInterface::class)->reveal(),
      'draft_published',
      'Publish',
      ['draft'],
      'published'
    );
    $this->assertEquals('draft_published', $state->id());
    $this->assertEquals('Publish', $state->label());
  }

  /**
   * Tests from and to.
   *
   * @legacy-covers ::from
   * @legacy-covers ::to
   */
  public function testFromAndTo(): void {
    $workflow = new TestType([], '', []);
    $workflow
      ->addState('draft', 'Draft')
      ->addState('published', 'Published')
      ->addTransition('publish', 'Publish', ['draft'], 'published');
    $state = $workflow->getState('draft');
    $transition = $state->getTransitionTo('published');
    $this->assertEquals($state, $transition->from()['draft']);
    $this->assertEquals($workflow->getState('published'), $transition->to());
  }

}
