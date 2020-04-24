<?php

namespace Drupal\Tests\block_content\Unit\Access;

use Drupal\block_content\Access\AccessGroupAnd;
use Drupal\Core\Access\AccessResult;
use Drupal\block_content\Access\RefinableDependentAccessInterface;
use Drupal\block_content\Access\RefinableDependentAccessTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass  \Drupal\block_content\Access\RefinableDependentAccessTrait
 *
 * @group block_content
 */
class DependentAccessTest extends UnitTestCase {
  use AccessibleTestingTrait;

  /**
   * An accessible object that results in forbidden access result.
   *
   * @var \Drupal\Core\Access\AccessibleInterface
   */
  protected $forbidden;

  /**
   * An accessible object that results in neutral access result.
   *
   * @var \Drupal\Core\Access\AccessibleInterface
   */
  protected $neutral;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->account = $this->prophesize(AccountInterface::class)->reveal();
    $this->forbidden = $this->createAccessibleDouble(AccessResult::forbidden('Because I said so'));
    $this->neutral = $this->createAccessibleDouble(AccessResult::neutral('I have no opinion'));
  }

  /**
   * Test that the previous dependency is replaced when using set.
   *
   * @covers ::setAccessDependency
   *
   * @dataProvider providerTestSetFirst
   */
  public function testSetAccessDependency($use_set_first) {
    $testRefinable = new RefinableDependentAccessTraitTestClass();

    if ($use_set_first) {
      $testRefinable->setAccessDependency($this->forbidden);
    }
    else {
      $testRefinable->addAccessDependency($this->forbidden);
    }
    $accessResult = $testRefinable->getAccessDependency()->access('view', $this->account, TRUE);
    $this->assertTrue($accessResult->isForbidden());
    $this->assertEquals('Because I said so', $accessResult->getReason());

    // Calling setAccessDependency() replaces the existing dependency.
    $testRefinable->setAccessDependency($this->neutral);
    $dependency = $testRefinable->getAccessDependency();
    $this->assertNotInstanceOf(AccessGroupAnd::class, $dependency);
    $accessResult = $dependency->access('view', $this->account, TRUE);
    $this->assertTrue($accessResult->isNeutral());
    $this->assertEquals('I have no opinion', $accessResult->getReason());
  }

  /**
   * Tests merging a new dependency with existing non-group access dependency.
   *
   * @dataProvider providerTestSetFirst
   */
  public function testMergeNonGroup($use_set_first) {
    $testRefinable = new RefinableDependentAccessTraitTestClass();
    if ($use_set_first) {
      $testRefinable->setAccessDependency($this->forbidden);
    }
    else {
      $testRefinable->addAccessDependency($this->forbidden);
    }

    $accessResult = $testRefinable->getAccessDependency()->access('view', $this->account, TRUE);
    $this->assertTrue($accessResult->isForbidden());
    $this->assertEquals('Because I said so', $accessResult->getReason());

    $testRefinable->addAccessDependency($this->neutral);
    /** @var \Drupal\block_content\Access\AccessGroupAnd $dependency */
    $dependency = $testRefinable->getAccessDependency();
    // Ensure the new dependency create a new AND group when merged.
    $this->assertInstanceOf(AccessGroupAnd::class, $dependency);
    $dependencies = $dependency->getDependencies();
    $accessResultForbidden = $dependencies[0]->access('view', $this->account, TRUE);
    $this->assertTrue($accessResultForbidden->isForbidden());
    $this->assertEquals('Because I said so', $accessResultForbidden->getReason());
    $accessResultNeutral = $dependencies[1]->access('view', $this->account, TRUE);
    $this->assertTrue($accessResultNeutral->isNeutral());
    $this->assertEquals('I have no opinion', $accessResultNeutral->getReason());
  }

  /**
   * Tests merging a new dependency with an existing access group dependency.
   *
   * @dataProvider providerTestSetFirst
   */
  public function testMergeGroup($use_set_first) {
    $andGroup = new AccessGroupAnd();
    $andGroup->addDependency($this->forbidden);
    $testRefinable = new RefinableDependentAccessTraitTestClass();
    if ($use_set_first) {
      $testRefinable->setAccessDependency($andGroup);
    }
    else {
      $testRefinable->addAccessDependency($andGroup);
    }

    $testRefinable->addAccessDependency($this->neutral);
    /** @var \Drupal\block_content\Access\AccessGroupAnd $dependency */
    $dependency = $testRefinable->getAccessDependency();

    // Ensure the new dependency is merged with the existing group.
    $this->assertInstanceOf(AccessGroupAnd::class, $dependency);
    $dependencies = $dependency->getDependencies();
    $accessResultForbidden = $dependencies[0]->access('view', $this->account, TRUE);
    $this->assertTrue($accessResultForbidden->isForbidden());
    $this->assertEquals('Because I said so', $accessResultForbidden->getReason());
    $accessResultNeutral = $dependencies[1]->access('view', $this->account, TRUE);
    $this->assertTrue($accessResultNeutral->isNeutral());
    $this->assertEquals('I have no opinion', $accessResultNeutral->getReason());
  }

  /**
   * Dataprovider for all test methods.
   *
   * Provides test cases for calling setAccessDependency() or
   * mergeAccessDependency() first. A call to either should behave the same on a
   * new RefinableDependentAccessInterface object.
   */
  public function providerTestSetFirst() {
    return [
      [TRUE],
      [FALSE],
    ];
  }

}

/**
 * Test class that implements RefinableDependentAccessInterface.
 */
class RefinableDependentAccessTraitTestClass implements RefinableDependentAccessInterface {

  use RefinableDependentAccessTrait;

}
