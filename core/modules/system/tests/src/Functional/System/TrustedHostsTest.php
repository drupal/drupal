<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests output on the status overview page.
 *
 * @group system
 */
class TrustedHostsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the status page behavior with no setting.
   *
   * Checks that an error is shown when the trusted host setting is missing from
   * settings.php
   */
  public function testStatusPageWithoutConfiguration(): void {
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains("Trusted Host Settings");
    $this->assertSession()->pageTextContains("The trusted_host_patterns setting is not configured in settings.php.");
  }

  /**
   * Tests that the status page shows the trusted patterns from settings.php.
   */
  public function testStatusPageWithConfiguration(): void {
    $settings['settings']['trusted_host_patterns'] = (object) [
      'value' => ['^' . preg_quote(\Drupal::request()->getHost()) . '$'],
      'required' => TRUE,
    ];

    $this->writeSettings($settings);

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains("Trusted Host Settings");
    $this->assertSession()->pageTextContains("The trusted_host_patterns setting is set to allow");
  }

  /**
   * Tests that fake requests have the proper host configured.
   *
   * @see \Drupal\Core\Http\TrustedHostsRequestFactory
   */
  public function testFakeRequests(): void {
    $this->container->get('module_installer')->install(['trusted_hosts_test']);

    $host = $this->container->get('request_stack')->getCurrentRequest()->getHost();
    $settings['settings']['trusted_host_patterns'] = (object) [
      'value' => ['^' . preg_quote($host) . '$'],
      'required' => TRUE,
    ];

    $this->writeSettings($settings);

    $this->drupalGet('trusted-hosts-test/fake-request');
    $this->assertSession()->pageTextContains('Host: ' . $host);
  }

  /**
   * Tests that shortcut module works together with host verification.
   */
  public function testShortcut(): void {
    $this->container->get('module_installer')->install(['block', 'shortcut']);
    $this->rebuildContainer();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $shortcut_storage = $entity_type_manager->getStorage('shortcut');

    $shortcut = $shortcut_storage->create([
      'title' => 'Test Shortcut Label',
      'link' => 'internal:/admin/reports/status',
      'shortcut_set' => 'default',
    ]);
    $shortcut_storage->save($shortcut);

    // Grant the current user access to see the shortcuts.
    $role_storage = $entity_type_manager->getStorage('user_role');
    $roles = $this->loggedInUser->getRoles(TRUE);
    /** @var \Drupal\user\RoleInterface $role */
    $role = $role_storage->load(reset($roles));
    $role->grantPermission('access shortcuts')->save();

    $this->drupalPlaceBlock('shortcuts');

    $this->drupalGet('');
    $this->assertSession()->linkExists($shortcut->label());
  }

  /**
   * Tests that the request bags have the correct classes.
   *
   * @todo Remove this when Symfony 4 is no longer supported.
   *
   * @see \Drupal\Core\Http\TrustedHostsRequestFactory
   */
  public function testRequestBags(): void {
    $this->container->get('module_installer')->install(['trusted_hosts_test']);

    $host = $this->container->get('request_stack')->getCurrentRequest()->getHost();
    $settings['settings']['trusted_host_patterns'] = (object) [
      'value' => ['^' . preg_quote($host) . '$'],
      'required' => TRUE,
    ];

    $this->writeSettings($settings);

    foreach (['request', 'query', 'cookies'] as $bag) {
      $this->drupalGet('trusted-hosts-test/bag-type/' . $bag);
      $this->assertSession()->pageTextContains('InputBag');
    }
  }

}
