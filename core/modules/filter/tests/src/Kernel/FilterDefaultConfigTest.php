<?php

namespace Drupal\Tests\filter\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests text format default configuration.
 *
 * @group filter
 */
class FilterDefaultConfigTest extends KernelTestBase {

  public static $modules = array('system', 'user', 'filter', 'filter_test');

  protected function setUp() {
    parent::setUp();

    // Drupal\filter\FilterPermissions::permissions() builds a URL to output
    // a link in the description.

    $this->installEntitySchema('user');

    // Install filter_test module, which ships with custom default format.
    $this->installConfig(array('user', 'filter_test'));
  }

  /**
   * Tests installation of default formats.
   */
  function testInstallation() {
    // Verify that the format was installed correctly.
    $format = entity_load('filter_format', 'filter_test');
    $this->assertTrue((bool) $format);
    $this->assertEqual($format->id(), 'filter_test');
    $this->assertEqual($format->label(), 'Test format');
    $this->assertEqual($format->get('weight'), 2);

    // Verify that format default property values have been added/injected.
    $this->assertTrue($format->uuid());

    // Verify that the loaded format does not contain any roles.
    $this->assertEqual($format->get('roles'), NULL);
    // Verify that the defined roles in the default config have been processed.
    $this->assertEqual(array_keys(filter_get_roles_by_format($format)), array(
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ));

    // Verify enabled filters.
    $filters = $format->get('filters');
    $this->assertEqual($filters['filter_html_escape']['status'], 1);
    $this->assertEqual($filters['filter_html_escape']['weight'], -10);
    $this->assertEqual($filters['filter_html_escape']['provider'], 'filter');
    $this->assertEqual($filters['filter_html_escape']['settings'], array());
    $this->assertEqual($filters['filter_autop']['status'], 1);
    $this->assertEqual($filters['filter_autop']['weight'], 0);
    $this->assertEqual($filters['filter_autop']['provider'], 'filter');
    $this->assertEqual($filters['filter_autop']['settings'], array());
    $this->assertEqual($filters['filter_url']['status'], 1);
    $this->assertEqual($filters['filter_url']['weight'], 0);
    $this->assertEqual($filters['filter_url']['provider'], 'filter');
    $this->assertEqual($filters['filter_url']['settings'], array(
      'filter_url_length' => 72,
    ));
  }

  /**
   * Tests that changes to FilterFormat::$roles do not have an effect.
   */
  function testUpdateRoles() {
    // Verify role permissions declared in default config.
    $format = entity_load('filter_format', 'filter_test');
    $this->assertEqual(array_keys(filter_get_roles_by_format($format)), array(
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ));

    // Attempt to change roles.
    $format->set('roles', array(
      RoleInterface::AUTHENTICATED_ID,
    ));
    $format->save();

    // Verify that roles have not been updated.
    $format = entity_load('filter_format', 'filter_test');
    $this->assertEqual(array_keys(filter_get_roles_by_format($format)), array(
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ));
  }

}
