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
   * Module handler for invoking user requirements.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->container->get('module_handler');
    $this->installEntitySchema('user');
    include_once $this->root . '/core/includes/install.inc';
  }

  /**
   * Tests that the requirements check can detect conflicting user emails.
   *
   * @see \Drupal\Tests\user\Kernel\UserValidationTest::testValidation
   */
  public function testConflictingUserEmails(): void {

    $output = $this->moduleHandler->invoke('user', 'runtime_requirements');
    $this->assertArrayNotHasKey('conflicting emails', $output);

    $this->createUser([], 'User A', FALSE, ['mail' => 'unique@example.com']);
    $this->createUser([], 'User B', FALSE, ['mail' => 'UNIQUE@example.com']);

    $output = $this->moduleHandler->invoke('user', 'runtime_requirements');
    $this->assertArrayHasKey('conflicting emails', $output);
  }

  /**
   * Tests that the requirements check does not incorrectly flag blank emails.
   */
  public function testBlankUserEmails(): void {

    $output = $this->moduleHandler->invoke('user', 'runtime_requirements');
    $this->assertArrayNotHasKey('conflicting emails', $output);

    $this->createUser([], 'User A', FALSE, ['mail' => '']);
    $this->createUser([], 'User B', FALSE, ['mail' => '']);

    $output = $this->moduleHandler->invoke('user', 'runtime_requirements');
    $this->assertArrayNotHasKey('conflicting emails', $output);
  }

}
