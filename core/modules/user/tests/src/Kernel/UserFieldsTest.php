<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests available user fields in twig.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
class UserFieldsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    // Set up a test theme that prints the user's mail field.
    \Drupal::service('theme_installer')->install(['user_test_theme']);
    \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('user_test_theme'));
    // Clear the theme registry.
    $this->container->set('theme.registry', NULL);
  }

  /**
   * Tests account's available fields.
   */
  public function testUserFields(): void {
    // Create the user to test the user fields.
    $user = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $build = \Drupal::entityTypeManager()
      ->getViewBuilder('user')
      ->view($user);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->setRawContent($output);
    $userEmail = $user->getEmail();
    $this->assertText($userEmail, "User's mail field is found in the twig template");
  }

}
