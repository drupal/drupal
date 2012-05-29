<?php

/**
 * @file
 * Definition of Drupal\update\Tests\UpdateTestBase.
 *
 * This file contains tests for the update module. The overarching methodology
 * of these tests is we need to compare a given state of installed modules and
 * themes (e.g. version, project grouping, timestamps, etc) vs. a current
 * state of what the release history XML files we fetch say is available.  We
 * have dummy XML files (in the 'tests' subdirectory) that describe various
 * scenarios of what's available for different test projects, and we have
 * dummy .info file data (specified via hook_system_info_alter() in the
 * update_test helper module) describing what's currently installed.  Each
 * test case defines a set of projects to install, their current state (via
 * the 'update_test_system_info' variable) and the desired available update
 * data (via the 'update_test_xml_map' variable), and then performs a series
 * of assertions that the report matches our expectations given the specific
 * initial state and availability scenario.
 */

namespace Drupal\update\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Base class to define some shared functions used by all update tests.
 */
class UpdateTestBase extends WebTestBase {
  /**
   * Refresh the update status based on the desired available update scenario.
   *
   * @param $xml_map
   *   Array that maps project names to availability scenarios to fetch.
   *   The key '#all' is used if a project-specific mapping is not defined.
   *
   * @see update_test_mock_page()
   */
  protected function refreshUpdateStatus($xml_map, $url = 'update-test') {
    // Tell update module to fetch from the URL provided by update_test module.
    variable_set('update_fetch_url', url($url, array('absolute' => TRUE)));
    // Save the map for update_test_mock_page() to use.
    variable_set('update_test_xml_map', $xml_map);
    // Manually check the update status.
    $this->drupalGet('admin/reports/updates/check');
  }

  /**
   * Run a series of assertions that are applicable for all update statuses.
   */
  protected function standardTests() {
    $this->assertRaw('<h3>' . t('Drupal core') . '</h3>');
    $this->assertRaw(l(t('Drupal'), 'http://example.com/project/drupal'), t('Link to the Drupal project appears.'));
    $this->assertNoText(t('No available releases found'));
  }
}
