<?php

/**
 * @file
 * Contains Drupal\filter\Tests\FilterDefaultConfigTest.
 */

namespace Drupal\filter\Tests;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests text format default configuration.
 */
class FilterDefaultConfigTest extends DrupalUnitTestBase {

  public static $modules = array('system', 'user', 'filter', 'filter_test', 'entity');

  public static function getInfo() {
    return array(
      'name' => 'Default configuration',
      'description' => 'Tests text format default configuration.',
      'group' => 'Filter',
    );
  }

  function setUp() {
    parent::setUp();

    // filter_permission() calls into url() to output a link in the description.
    $this->installSchema('system', 'url_alias');

    $this->installSchema('user', array('users_roles'));

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
    $this->assertEqual($format->get('cache'), 1);

    // Verify that the loaded format does not contain any roles.
    $this->assertEqual($format->get('roles'), NULL);
    // Verify that the defined roles in the default config have been processed.
    $this->assertEqual(array_keys(filter_get_roles_by_format($format)), array(
      DRUPAL_ANONYMOUS_RID,
      DRUPAL_AUTHENTICATED_RID,
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
      DRUPAL_ANONYMOUS_RID,
      DRUPAL_AUTHENTICATED_RID,
    ));

    // Attempt to change roles.
    $format->set('roles', array(
      DRUPAL_AUTHENTICATED_RID,
    ));
    $format->save();

    // Verify that roles have not been updated.
    $format = entity_load('filter_format', 'filter_test');
    $this->assertEqual(array_keys(filter_get_roles_by_format($format)), array(
      DRUPAL_ANONYMOUS_RID,
      DRUPAL_AUTHENTICATED_RID,
    ));
  }

}
