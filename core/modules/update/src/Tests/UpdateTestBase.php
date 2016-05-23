<?php

namespace Drupal\update\Tests;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Defines some shared functions used by all update tests.
 *
 * The overarching methodology of these tests is we need to compare a given
 * state of installed modules and themes (e.g., version, project grouping,
 * timestamps, etc) against a current state of what the release history XML
 * files we fetch say is available. We have dummy XML files (in the
 * core/modules/update/tests directory) that describe various scenarios of
 * what's available for different test projects, and we have dummy .info file
 * data (specified via hook_system_info_alter() in the update_test helper
 * module) describing what's currently installed. Each test case defines a set
 * of projects to install, their current state (via the
 * 'update_test_system_info' variable) and the desired available update data
 * (via the 'update_test_xml_map' variable), and then performs a series of
 * assertions that the report matches our expectations given the specific
 * initial state and availability scenario.
 */
abstract class UpdateTestBase extends WebTestBase {

  protected function setUp() {
    parent::setUp();

    // Change the root path which Update Manager uses to install and update
    // projects to be inside the testing site directory. See
    // \Drupal\update\UpdateRootFactory::get() for equivalent changes to the
    // test child site.
    $request = \Drupal::request();
    $update_root = $this->container->get('update.root') . '/' . DrupalKernel::findSitePath($request);
    $this->container->set('update.root', $update_root);
    \Drupal::setContainer($this->container);

    // Create the directories within the root path within which the Update
    // Manager will install projects.
    foreach (drupal_get_updaters() as $updater_info) {
      $updater = $updater_info['class'];
      $install_directory = $update_root . '/' . $updater::getRootDirectoryRelativePath();
      if (!is_dir($install_directory)) {
        mkdir($install_directory);
      }
    }
  }

  /**
   * Refreshes the update status based on the desired available update scenario.
   *
   * @param $xml_map
   *   Array that maps project names to availability scenarios to fetch. The key
   *   '#all' is used if a project-specific mapping is not defined.
   * @param $url
   *   (optional) A string containing the URL to fetch update data from.
   *   Defaults to 'update-test'.
   *
   * @see Drupal\update_test\Controller\UpdateTestController::updateTest()
   */
  protected function refreshUpdateStatus($xml_map, $url = 'update-test') {
    // Tell the Update Manager module to fetch from the URL provided by
    // update_test module.
    $this->config('update.settings')->set('fetch.url', Url::fromUri('base:' . $url, array('absolute' => TRUE))->toString())->save();
    // Save the map for UpdateTestController::updateTest() to use.
    $this->config('update_test.settings')->set('xml_map', $xml_map)->save();
    // Manually check the update status.
    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
  }

  /**
   * Runs a series of assertions that are applicable to all update statuses.
   */
  protected function standardTests() {
    $this->assertRaw('<h3>' . t('Drupal core') . '</h3>');
    $this->assertRaw(\Drupal::l(t('Drupal'), Url::fromUri('http://example.com/project/drupal')), 'Link to the Drupal project appears.');
    $this->assertNoText(t('No available releases found'));
  }

}
