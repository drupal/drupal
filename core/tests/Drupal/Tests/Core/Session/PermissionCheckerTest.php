<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Session\AccessPolicyProcessorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissions;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\PermissionChecker;
use Drupal\Core\Session\RefinableCalculatedPermissions;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Session\PermissionChecker
 * @group Session
 */
class PermissionCheckerTest extends UnitTestCase {

  /**
   * The permission checker to run tests on.
   *
   * @var \Drupal\Core\Session\PermissionChecker
   */
  protected $checker;

  /**
   * The mocked access policy processor.
   *
   * @var \Drupal\Core\Session\AccessPolicyProcessorInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $processor;

  /**
   * The mocked account to use for testing.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->processor = $this->prophesize(AccessPolicyProcessorInterface::class);
    $this->checker = new PermissionChecker($this->processor->reveal());
    $this->account = $this->prophesize(AccountInterface::class)->reveal();
  }

  /**
   * Tests the hasPermission method under normal circumstances.
   */
  public function testHasPermission(): void {
    $calculated_permissions = new CalculatedPermissions(
      (new RefinableCalculatedPermissions())->addItem(
        new CalculatedPermissionsItem(['foo'])
      )
    );
    $this->processor->processAccessPolicies($this->account)->willReturn($calculated_permissions);
    $this->assertTrue($this->checker->hasPermission('foo', $this->account));
    $this->assertFalse($this->checker->hasPermission('bar', $this->account));
  }

  /**
   * Tests the hasPermission method when no policy added something.
   */
  public function testHasPermissionEmpty(): void {
    $calculated_permissions = new CalculatedPermissions(new RefinableCalculatedPermissions());
    $this->processor->processAccessPolicies($this->account)->willReturn($calculated_permissions);
    $this->assertFalse($this->checker->hasPermission('foo', $this->account));
    $this->assertFalse($this->checker->hasPermission('bar', $this->account));
  }

  /**
   * Tests the hasPermission method when mixed scopes and identifiers exist.
   */
  public function testHasPermissionMixed(): void {
    $calculated_permissions = new CalculatedPermissions(
      (new RefinableCalculatedPermissions())->addItem(
        new CalculatedPermissionsItem(['foo'])
      )->addItem(
        new CalculatedPermissionsItem(['bar'], identifier: 'other-identifier')
      )->addItem(
        new CalculatedPermissionsItem(['baz'], FALSE, 'other-scope', 'other-identifier')
      )
    );
    $this->processor->processAccessPolicies($this->account)->willReturn($calculated_permissions);
    $this->assertTrue($this->checker->hasPermission('foo', $this->account));
    $this->assertFalse($this->checker->hasPermission('bar', $this->account));
    $this->assertFalse($this->checker->hasPermission('baz', $this->account));
  }

  /**
   * Tests the hasPermission method with only contrib scopes and identifiers.
   */
  public function testHasPermissionOnlyContrib(): void {
    $calculated_permissions = new CalculatedPermissions(
      (new RefinableCalculatedPermissions())->addItem(
        new CalculatedPermissionsItem(['baz'], FALSE, 'other-scope', 'other-identifier')
      )
    );
    $this->processor->processAccessPolicies($this->account)->willReturn($calculated_permissions);
    $this->assertFalse($this->checker->hasPermission('foo', $this->account));
    $this->assertFalse($this->checker->hasPermission('bar', $this->account));
    $this->assertFalse($this->checker->hasPermission('baz', $this->account));
  }

}
