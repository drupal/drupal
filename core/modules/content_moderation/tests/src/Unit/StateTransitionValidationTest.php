<?php

namespace Drupal\Tests\content_moderation\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\content_moderation\ModerationStateInterface;
use Drupal\content_moderation\ModerationStateTransitionInterface;
use Drupal\content_moderation\StateTransitionValidation;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\content_moderation\StateTransitionValidation
 * @group content_moderation
 */
class StateTransitionValidationTest extends \PHPUnit_Framework_TestCase {

  /**
   * Builds a mock storage object for Transitions.
   *
   * @return EntityStorageInterface
   *   The mocked storage object for Transitions.
   */
  protected function setupTransitionStorage() {
    $entity_storage = $this->prophesize(EntityStorageInterface::class);

    $list = $this->setupTransitionEntityList();
    $entity_storage->loadMultiple()->willReturn($list);
    $entity_storage->loadMultiple(Argument::type('array'))->will(function ($args) use ($list) {
      $keys = $args[0];
      if (empty($keys)) {
        return $list;
      }

      $return = array_map(function($key) use ($list) {
        return $list[$key];
      }, $keys);

      return $return;
    });
    return $entity_storage->reveal();
  }

  /**
   * Builds an array of mocked Transition objects.
   *
   * @return ModerationStateTransitionInterface[]
   *   An array of mocked Transition objects.
   */
  protected function setupTransitionEntityList() {
    $transition = $this->prophesize(ModerationStateTransitionInterface::class);
    $transition->id()->willReturn('draft__needs_review');
    $transition->getFromState()->willReturn('draft');
    $transition->getToState()->willReturn('needs_review');
    $list[$transition->reveal()->id()] = $transition->reveal();

    $transition = $this->prophesize(ModerationStateTransitionInterface::class);
    $transition->id()->willReturn('needs_review__staging');
    $transition->getFromState()->willReturn('needs_review');
    $transition->getToState()->willReturn('staging');
    $list[$transition->reveal()->id()] = $transition->reveal();

    $transition = $this->prophesize(ModerationStateTransitionInterface::class);
    $transition->id()->willReturn('staging__published');
    $transition->getFromState()->willReturn('staging');
    $transition->getToState()->willReturn('published');
    $list[$transition->reveal()->id()] = $transition->reveal();

    $transition = $this->prophesize(ModerationStateTransitionInterface::class);
    $transition->id()->willReturn('needs_review__draft');
    $transition->getFromState()->willReturn('needs_review');
    $transition->getToState()->willReturn('draft');
    $list[$transition->reveal()->id()] = $transition->reveal();

    $transition = $this->prophesize(ModerationStateTransitionInterface::class);
    $transition->id()->willReturn('draft__draft');
    $transition->getFromState()->willReturn('draft');
    $transition->getToState()->willReturn('draft');
    $list[$transition->reveal()->id()] = $transition->reveal();

    $transition = $this->prophesize(ModerationStateTransitionInterface::class);
    $transition->id()->willReturn('needs_review__needs_review');
    $transition->getFromState()->willReturn('needs_review');
    $transition->getToState()->willReturn('needs_review');
    $list[$transition->reveal()->id()] = $transition->reveal();

    $transition = $this->prophesize(ModerationStateTransitionInterface::class);
    $transition->id()->willReturn('published__published');
    $transition->getFromState()->willReturn('published');
    $transition->getToState()->willReturn('published');
    $list[$transition->reveal()->id()] = $transition->reveal();

    return $list;
  }

  /**
   * Builds a mock storage object for States.
   *
   * @return EntityStorageInterface
   *   The mocked storage object for States.
   */
  protected function setupStateStorage() {
    $entity_storage = $this->prophesize(EntityStorageInterface::class);

    $state = $this->prophesize(ModerationStateInterface::class);
    $state->id()->willReturn('draft');
    $state->label()->willReturn('Draft');
    $state->isPublishedState()->willReturn(FALSE);
    $state->isDefaultRevisionState()->willReturn(FALSE);
    $states['draft'] = $state->reveal();

    $state = $this->prophesize(ModerationStateInterface::class);
    $state->id()->willReturn('needs_review');
    $state->label()->willReturn('Needs Review');
    $state->isPublishedState()->willReturn(FALSE);
    $state->isDefaultRevisionState()->willReturn(FALSE);
    $states['needs_review'] = $state->reveal();

    $state = $this->prophesize(ModerationStateInterface::class);
    $state->id()->willReturn('staging');
    $state->label()->willReturn('Staging');
    $state->isPublishedState()->willReturn(FALSE);
    $state->isDefaultRevisionState()->willReturn(FALSE);
    $states['staging'] = $state->reveal();

    $state = $this->prophesize(ModerationStateInterface::class);
    $state->id()->willReturn('published');
    $state->label()->willReturn('Published');
    $state->isPublishedState()->willReturn(TRUE);
    $state->isDefaultRevisionState()->willReturn(TRUE);
    $states['published'] = $state->reveal();

    $state = $this->prophesize(ModerationStateInterface::class);
    $state->id()->willReturn('archived');
    $state->label()->willReturn('Archived');
    $state->isPublishedState()->willReturn(TRUE);
    $state->isDefaultRevisionState()->willReturn(TRUE);
    $states['archived'] = $state->reveal();

    $entity_storage->loadMultiple()->willReturn($states);

    foreach ($states as $id => $state) {
      $entity_storage->load($id)->willReturn($state);
    }

    return $entity_storage->reveal();
  }

  /**
   * Builds a mocked Entity Type Manager.
   *
   * @return EntityTypeManagerInterface
   *   The mocked Entity Type Manager.
   */
  protected function setupEntityTypeManager(EntityStorageInterface $storage) {
    $entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $entityTypeManager->getStorage('moderation_state')->willReturn($storage);
    $entityTypeManager->getStorage('moderation_state_transition')->willReturn($this->setupTransitionStorage());

    return $entityTypeManager->reveal();
  }

  /**
   * Builds a mocked query factory that does nothing.
   *
   * @return QueryFactory
   *   The mocked query factory that does nothing.
   */
  protected function setupQueryFactory() {
    $factory = $this->prophesize(QueryFactory::class);

    return $factory->reveal();
  }

  /**
   * @covers ::isTransitionAllowed
   * @covers ::calculatePossibleTransitions
   *
   * @dataProvider providerIsTransitionAllowedWithValidTransition
   */
  public function testIsTransitionAllowedWithValidTransition($from_id, $to_id) {
    $storage = $this->setupStateStorage();
    $state_transition_validation = new StateTransitionValidation($this->setupEntityTypeManager($storage), $this->setupQueryFactory());
    $this->assertTrue($state_transition_validation->isTransitionAllowed($storage->load($from_id), $storage->load($to_id)));
  }

  /**
   * Data provider for self::testIsTransitionAllowedWithValidTransition().
   */
  public function providerIsTransitionAllowedWithValidTransition() {
    return [
      ['draft', 'draft'],
      ['draft', 'needs_review'],
      ['needs_review', 'needs_review'],
      ['needs_review', 'staging'],
      ['staging', 'published'],
      ['needs_review', 'draft'],
    ];
  }

  /**
   * @covers ::isTransitionAllowed
   * @covers ::calculatePossibleTransitions
   *
   * @dataProvider providerIsTransitionAllowedWithInValidTransition
   */
  public function testIsTransitionAllowedWithInValidTransition($from_id, $to_id) {
    $storage = $this->setupStateStorage();
    $state_transition_validation = new StateTransitionValidation($this->setupEntityTypeManager($storage), $this->setupQueryFactory());
    $this->assertFalse($state_transition_validation->isTransitionAllowed($storage->load($from_id), $storage->load($to_id)));
  }

  /**
   * Data provider for self::testIsTransitionAllowedWithInValidTransition().
   */
  public function providerIsTransitionAllowedWithInValidTransition() {
    return [
      ['published', 'needs_review'],
      ['published', 'staging'],
      ['staging', 'needs_review'],
      ['staging', 'staging'],
      ['needs_review', 'published'],
      ['published', 'archived'],
      ['archived', 'published'],
    ];
  }

  /**
   * Verifies user-aware transition validation.
   *
   * @param string $from_id
   *   The state to transition from.
   * @param string $to_id
   *   The state to transition to.
   * @param string $permission
   *   The permission to give the user, or not.
   * @param bool $allowed
   *   Whether or not to grant a user this permission.
   * @param bool $result
   *   Whether userMayTransition() is expected to return TRUE or FALSE.
   *
   * @dataProvider userTransitionsProvider
   */
  public function testUserSensitiveValidTransitions($from_id, $to_id, $permission, $allowed, $result) {
    $user = $this->prophesize(AccountInterface::class);
    // The one listed permission will be returned as instructed; Any others are
    // always denied.
    $user->hasPermission($permission)->willReturn($allowed);
    $user->hasPermission(Argument::type('string'))->willReturn(FALSE);

    $storage = $this->setupStateStorage();
    $validator = new Validator($this->setupEntityTypeManager($storage), $this->setupQueryFactory());

    $this->assertEquals($result, $validator->userMayTransition($storage->load($from_id), $storage->load($to_id), $user->reveal()));
  }

  /**
   * Data provider for the user transition test.
   */
  public function userTransitionsProvider() {
    // The user has the right permission, so let it through.
    $ret[] = ['draft', 'draft', 'use draft__draft transition', TRUE, TRUE];

    // The user doesn't have the right permission, block it.
    $ret[] = ['draft', 'draft', 'use draft__draft transition', FALSE, FALSE];

    // The user has some other permission that doesn't matter.
    $ret[] = ['draft', 'draft', 'use draft__needs_review transition', TRUE, FALSE];

    // The user has permission, but the transition isn't allowed anyway.
    $ret[] = ['published', 'needs_review', 'use published__needs_review transition', TRUE, FALSE];

    return $ret;
  }

}

/**
 * Testable subclass for selected tests.
 *
 * EntityQuery is beyond untestable, so we have to subclass and override the
 * method that uses it.
 */
class Validator extends StateTransitionValidation {

  /**
   * {@inheritdoc}
   */
  protected function getTransitionFromStates(ModerationStateInterface $from, ModerationStateInterface $to) {
    if ($from->id() === 'draft' && $to->id() === 'draft') {
      return $this->transitionStorage()->loadMultiple(['draft__draft'])[0];
    }
  }

}
