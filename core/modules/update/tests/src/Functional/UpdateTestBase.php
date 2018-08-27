<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

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
abstract class UpdateTestBase extends BrowserTestBase {

  /**
   * Denotes a security update will be required in the test case.
   */
  const SECURITY_UPDATE_REQUIRED = 'SECURITY_UPDATE_REQUIRED';

  /**
   * Denotes an update will be available in the test case.
   */
  const UPDATE_AVAILABLE = 'UPDATE_AVAILABLE';

  /**
   * Denotes no update will be available in the test case.
   */
  const UPDATE_NONE = 'UPDATE_NONE';

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
   * @see \Drupal\update_test\Controller\UpdateTestController::updateTest()
   */
  protected function refreshUpdateStatus($xml_map, $url = 'update-test') {
    // Tell the Update Manager module to fetch from the URL provided by
    // update_test module.
    $this->config('update.settings')->set('fetch.url', Url::fromUri('base:' . $url, ['absolute' => TRUE])->toString())->save();
    // Save the map for UpdateTestController::updateTest() to use.
    $this->config('update_test.settings')->set('xml_map', $xml_map)->save();
    // Manually check the update status.
    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->checkForMetaRefresh();
  }

  /**
   * Runs a series of assertions that are applicable to all update statuses.
   */
  protected function standardTests() {
    $this->assertRaw('<h3>' . t('Drupal core') . '</h3>');
    $this->assertRaw(\Drupal::l(t('Drupal'), Url::fromUri('http://example.com/project/drupal')), 'Link to the Drupal project appears.');
    $this->assertNoText(t('No available releases found'));
  }

  /**
   * Asserts the expected security updates are displayed correctly on the page.
   *
   * @param string $project_path_part
   *   The project path part needed for the download and release links.
   * @param string[] $expected_security_releases
   *   The security releases, if any, that the status report should recommend.
   * @param string $expected_update_message_type
   *   The type of update message expected.
   * @param string $update_element_css_locator
   *   The CSS locator for the page element that contains the security updates.
   */
  protected function assertSecurityUpdates($project_path_part, array $expected_security_releases, $expected_update_message_type, $update_element_css_locator) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->standardTests();
    $assert_session->elementTextNotContains('css', $update_element_css_locator, 'Not supported');
    $all_security_release_urls = array_map(function ($link) {
      return $link->getAttribute('href');
    }, $page->findAll('css', "$update_element_css_locator .version-security a[href$='-release']"));
    $all_security_download_urls = array_map(function ($link) {
      return $link->getAttribute('href');
    }, $page->findAll('css', "$update_element_css_locator .version-security a[href$='.tar.gz']"));
    if ($expected_security_releases) {
      $expected_download_urls = [];
      $expected_release_urls = [];
      if ($expected_update_message_type === static::SECURITY_UPDATE_REQUIRED) {
        $assert_session->elementTextNotContains('css', $update_element_css_locator, 'Update available');
        $assert_session->elementTextContains('css', $update_element_css_locator, 'Security update required!');
        $assert_session->responseContains('error.svg', 'Error icon was found.');
      }
      else {
        $assert_session->elementTextContains('css', $update_element_css_locator, 'Update available');
        $assert_session->elementTextNotContains('css', $update_element_css_locator, 'Security update required!');
      }
      $assert_session->elementTextNotContains('css', $update_element_css_locator, 'Up to date');
      foreach ($expected_security_releases as $expected_security_release) {
        $expected_url_version = str_replace('.', '-', $expected_security_release);
        $release_url = "http://example.com/$project_path_part-$expected_url_version-release";
        $download_url = "http://example.com/$project_path_part-$expected_url_version.tar.gz";
        $expected_release_urls[] = $release_url;
        $expected_download_urls[] = $download_url;
        // Ensure the expected links are security links.
        $this->assertTrue(in_array($release_url, $all_security_release_urls), "Release $release_url is a security release link.");
        $this->assertTrue(in_array($download_url, $all_security_download_urls), "Release $download_url is a security download link.");
        $assert_session->linkByHrefExists($release_url);
        $assert_session->linkByHrefExists($download_url);
      }
      // Ensure no other links are shown as security releases.
      $this->assertEquals([], array_diff($all_security_release_urls, $expected_release_urls));
      $this->assertEquals([], array_diff($all_security_download_urls, $expected_download_urls));
    }
    else {
      // Ensure there were no security links.
      $this->assertEquals([], $all_security_release_urls);
      $this->assertEquals([], $all_security_download_urls);
      $assert_session->pageTextNotContains('Security update required!');
      if ($expected_update_message_type === static::UPDATE_AVAILABLE) {
        $assert_session->elementTextContains('css', $update_element_css_locator, 'Update available');
        $assert_session->elementTextNotContains('css', $update_element_css_locator, 'Up to date');
      }
      elseif ($expected_update_message_type === static::UPDATE_NONE) {
        $assert_session->elementTextNotContains('css', $update_element_css_locator, 'Update available');
        $assert_session->elementTextContains('css', $update_element_css_locator, 'Up to date');
      }
      else {
        $this->fail('Unexpected value for $expected_update_message_type: ' . $expected_update_message_type);
      }
    }
  }

}
