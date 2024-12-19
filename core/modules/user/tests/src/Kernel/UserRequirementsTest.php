<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests user_requirements().
 *
 * @group user
 */
class UserRequirementsTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_handler')->loadInclude('user', 'install');
    $this->installEntitySchema('user');
  }

  /**
   * Tests that the requirements check can detect conflicting user emails.
   *
   * @see \Drupal\Tests\user\Kernel\UserValidationTest::testValidation
   */
  public function testConflictingUserEmails(): void {

    $output = \user_requirements('runtime');
    $this->assertArrayNotHasKey('conflicting emails', $output);

    $this->createUser([], 'User A', FALSE, ['mail' => 'unique@example.com']);
    $this->createUser([], 'User B', FALSE, ['mail' => 'UNIQUE@example.com']);

    $output = \user_requirements('runtime');
    $this->assertArrayHasKey('conflicting emails', $output);
  }

  /**
   * Tests that the requirements check does not incorrectly flag blank emails.
   */
  public function testBlankUserEmails(): void {

    $output = \user_requirements('runtime');
    $this->assertArrayNotHasKey('conflicting emails', $output);

    $this->createUser([], 'User A', FALSE, ['mail' => '']);
    $this->createUser([], 'User B', FALSE, ['mail' => '']);

    $output = \user_requirements('runtime');
    $this->assertArrayNotHasKey('conflicting emails', $output);
  }

}
