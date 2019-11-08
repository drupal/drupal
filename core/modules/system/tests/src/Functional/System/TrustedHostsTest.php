<?php

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
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that the status page shows an error when the trusted host setting
   * is missing from settings.php
   */
  public function testStatusPageWithoutConfiguration() {
    $this->drupalGet('admin/reports/status');
    $this->assertResponse(200, 'The status page is reachable.');

    $this->assertRaw(t('Trusted Host Settings'));
    $this->assertRaw(t('The trusted_host_patterns setting is not configured in settings.php.'));
  }

  /**
   * Tests that the status page shows the trusted patterns from settings.php.
   */
  public function testStatusPageWithConfiguration() {
    $settings['settings']['trusted_host_patterns'] = (object) [
      'value' => ['^' . preg_quote(\Drupal::request()->getHost()) . '$'],
      'required' => TRUE,
    ];

    $this->writeSettings($settings);

    $this->drupalGet('admin/reports/status');
    $this->assertResponse(200, 'The status page is reachable.');

    $this->assertRaw(t('Trusted Host Settings'));
    $this->assertRaw(t('The trusted_host_patterns setting is set to allow'));
  }

  /**
   * Tests that fake requests have the proper host configured.
   *
   * @see \Drupal\Core\Http\TrustedHostsRequestFactory
   */
  public function testFakeRequests() {
    $this->container->get('module_installer')->install(['trusted_hosts_test']);
    $this->container->get('router.builder')->rebuild();

    $host = $this->container->get('request_stack')->getCurrentRequest()->getHost();
    $settings['settings']['trusted_host_patterns'] = (object) [
      'value' => ['^' . preg_quote($host) . '$'],
      'required' => TRUE,
    ];

    $this->writeSettings($settings);

    $this->drupalGet('trusted-hosts-test/fake-request');
    $this->assertText('Host: ' . $host);
  }

  /**
   * Tests that shortcut module works together with host verification.
   */
  public function testShortcut() {
    $this->container->get('module_installer')->install(['block', 'shortcut']);
    $this->rebuildContainer();
    $this->container->get('router.builder')->rebuild();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $shortcut_storage = $entity_type_manager->getStorage('shortcut');

    $shortcut = $shortcut_storage->create([
      'title' => $this->randomString(),
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
    $this->assertLink($shortcut->label());
  }

}
