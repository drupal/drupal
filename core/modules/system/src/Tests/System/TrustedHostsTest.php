<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\TrustedHostsTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests output on the status overview page.
 *
 * @group system
 */
class TrustedHostsTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array(
      'administer site configuration',
    ));
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
    $settings['settings']['trusted_host_patterns'] = (object) array(
      'value' => array('^' . preg_quote(\Drupal::request()->getHost()) . '$'),
      'required' => TRUE,
    );

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

    $host = $this->container->get('request_stack')->getCurrentRequest()->getHost();
    $settings['settings']['trusted_host_patterns'] = (object) array(
      'value' => array('^' . preg_quote($host) . '$'),
      'required' => TRUE,
    );

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

    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $this->container->get('entity.manager');
    $shortcut_storage = $entity_manager->getStorage('shortcut');

    $shortcut = $shortcut_storage->create([
      'title' => $this->randomString(),
      'link' => 'internal:/admin/reports/status',
      'shortcut_set' => 'default',
    ]);
    $shortcut_storage->save($shortcut);

    // Grant the current user access to see the shortcuts.
    $role_storage = $entity_manager->getStorage('user_role');
    $roles = $this->loggedInUser->getRoles(TRUE);
    /** @var \Drupal\user\RoleInterface $role */
    $role = $role_storage->load(reset($roles));
    $role->grantPermission('access shortcuts')->save();

    $this->drupalPlaceBlock('shortcuts');

    $this->drupalGet('');
    $this->assertLink($shortcut->label());
  }

}
