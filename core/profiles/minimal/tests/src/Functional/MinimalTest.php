<?php

declare(strict_types=1);

namespace Drupal\Tests\minimal\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\RequirementsPageTrait;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\user\UserInterface;

/**
 * Tests Minimal installation profile expectations.
 *
 * @group minimal
 */
class MinimalTest extends BrowserTestBase {

  use SchemaCheckTestTrait;
  use RequirementsPageTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Minimal installation profile.
   */
  public function testMinimal(): void {
    $this->drupalGet('');
    // Check the login block is present.
    $this->assertSession()->buttonExists('Log in');
    // Confirm anonymous users cannot create an account.
    $this->assertSession()->linkNotExists('Create new account');
    $this->assertSession()->statusCodeEquals(200);

    // Create a user to test tools and navigation blocks for logged in users
    // with appropriate permissions.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
    ]);
    $this->drupalLogin($user);
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('Tools');
    $this->assertSession()->pageTextContains('Administration');

    // Ensure that there are no pending updates after installation.
    $this->drupalLogin($this->drupalCreateUser([
      'administer software updates',
    ]));
    $this->drupalGet('update.php/selection');
    $this->updateRequirementsProblem();
    $this->drupalGet('update.php/selection');
    $this->assertSession()->pageTextContains('No pending updates.');

    // Ensure that there are no pending entity updates after installation.
    $this->assertFalse($this->container->get('entity.definition_update_manager')->needsUpdates(), 'After installation, entity schema is up to date.');

    // Ensure special configuration overrides are correct.
    $this->assertFalse($this->config('system.theme.global')->get('features.node_user_picture'), 'Configuration system.theme.global:features.node_user_picture is FALSE.');
    $this->assertEquals(UserInterface::REGISTER_ADMINISTRATORS_ONLY, $this->config('user.settings')->get('register'));

    // Now we have all configuration imported, test all of them for schema
    // conformance. Ensures all imported default configuration is valid when
    // Minimal profile modules are enabled.
    $names = $this->container->get('config.storage')->listAll();
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config */
    $typed_config = $this->container->get('config.typed');
    foreach ($names as $name) {
      $config = $this->config($name);
      $this->assertConfigSchema($typed_config, $name, $config->get());
    }
  }

}
