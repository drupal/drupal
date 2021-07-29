<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Link;
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

  /**
   * The CSS locator for the update table run asserts on.
   *
   * @var string
   */
  protected $updateTableLocator;

  /**
   * The project that is being tested.
   *
   * @var string
   */
  protected $updateProject;

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
    $this->clickLink('Check manually');
    $this->checkForMetaRefresh();
  }

  /**
   * Runs a series of assertions that are applicable to all update statuses.
   */
  protected function standardTests() {
    $this->assertSession()->responseContains('<h3>Drupal core</h3>');
    // Verify that the link to the Drupal project appears.
    $this->assertRaw(Link::fromTextAndUrl(t('Drupal'), Url::fromUri('http://example.com/project/drupal'))->toString());
    $this->assertNoText('No available releases found');
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
        // Verify that the error icon is found.
        $assert_session->responseContains('error.svg');
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
        $this->assertContains($release_url, $all_security_release_urls, "Release $release_url is a security release link.");
        $this->assertContains($download_url, $all_security_download_urls, "Release $download_url is a security download link.");
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

  /**
   * Asserts that an update version has the correct links.
   *
   * @param string $label
   *   The label for the update.
   * @param string $version
   *   The project version.
   * @param string|null $download_version
   *   (optional) The version number as it appears in the download link. If
   *   $download_version is not provided then $version will be used.
   */
  protected function assertVersionUpdateLinks($label, $version, $download_version = NULL) {
    $download_version = $download_version ?? $version;
    $update_element = $this->findUpdateElementByLabel($label);
    // In the release notes URL the periods are replaced with dashes.
    $url_version = str_replace('.', '-', $version);

    $this->assertEquals($update_element->findLink($version)->getAttribute('href'), "http://example.com/{$this->updateProject}-$url_version-release");
    $this->assertEquals($update_element->findLink('Download')->getAttribute('href'), "http://example.com/{$this->updateProject}-$download_version.tar.gz");
    $this->assertEquals($update_element->findLink('Release notes')->getAttribute('href'), "http://example.com/{$this->updateProject}-$url_version-release");
  }

  /**
   * Confirms messages are correct when a release has been unpublished/revoked.
   *
   * @param string $revoked_version
   *   The revoked version that is currently installed.
   * @param string $newer_version
   *   The expected newer version to recommend.
   * @param string $new_version_label
   *   The expected label for the newer version (for example 'Recommended
   *   version:' or 'Also available:').
   */
  protected function confirmRevokedStatus($revoked_version, $newer_version, $new_version_label) {
    $this->drupalGet('admin/reports/updates');
    $this->clickLink('Check manually');
    $this->checkForMetaRefresh();
    $this->assertUpdateTableTextContains('Revoked!');
    $this->assertUpdateTableTextContains($revoked_version);
    $this->assertUpdateTableElementContains('error.svg');
    $this->assertUpdateTableTextContains('Release revoked: Your currently installed release has been revoked, and is no longer available for download. Disabling everything included in this release or upgrading is strongly recommended!');
    $this->assertVersionUpdateLinks($new_version_label, $newer_version);
  }

  /**
   * Confirms messages are correct when a release has been marked unsupported.
   *
   * @param string $unsupported_version
   *   The unsupported version that is currently installed.
   * @param string $newer_version
   *   The expected newer version to recommend.
   * @param string $new_version_label
   *   The expected label for the newer version (for example 'Recommended
   *   version:' or 'Also available:').
   */
  protected function confirmUnsupportedStatus($unsupported_version, $newer_version, $new_version_label) {
    $this->drupalGet('admin/reports/updates');
    $this->clickLink('Check manually');
    $this->checkForMetaRefresh();
    $this->assertUpdateTableTextContains('Not supported!');
    $this->assertUpdateTableTextContains($unsupported_version);
    $this->assertUpdateTableElementContains('error.svg');
    $this->assertUpdateTableTextContains('Release not supported: Your currently installed release is now unsupported, and is no longer available for download. Disabling everything included in this release or upgrading is strongly recommended!');
    $this->assertVersionUpdateLinks($new_version_label, $newer_version);
  }

  /**
   * Asserts that the update table text contains the specified text.
   *
   * @param string $text
   *   The expected text.
   *
   * @see \Behat\Mink\WebAssert::elementTextContains()
   */
  protected function assertUpdateTableTextContains($text) {
    $this->assertSession()
      ->elementTextContains('css', $this->updateTableLocator, $text);
  }

  /**
   * Asserts that the update table text does not contain the specified text.
   *
   * @param string $text
   *   The expected text.
   */
  protected function assertUpdateTableTextNotContains($text) {
    $this->assertSession()->elementTextNotContains('css', $this->updateTableLocator, $text);
  }

  /**
   * Asserts that the update table element HTML contains the specified text.
   *
   * @param string $text
   *   The expected text.
   *
   * @see \Behat\Mink\WebAssert::elementContains()
   */
  protected function assertUpdateTableElementContains($text) {
    $this->assertSession()
      ->elementContains('css', $this->updateTableLocator, $text);
  }

  /**
   * Asserts that the update table element HTML contains the specified text.
   *
   * @param string $text
   *   The expected text.
   *
   * @see \Behat\Mink\WebAssert::elementNotContains()
   */
  protected function assertUpdateTableElementNotContains($text) {
    $this->assertSession()
      ->elementNotContains('css', $this->updateTableLocator, $text);
  }

  /**
   * Finds an update page element by label.
   *
   * @param string $label
   *   The label for the update, for example "Recommended version:" or
   *   "Latest version:".
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The update element.
   */
  protected function findUpdateElementByLabel($label) {
    $update_elements = $this->getSession()->getPage()
      ->findAll('css', $this->updateTableLocator . " .project-update__version:contains(\"$label\")");
    $this->assertCount(1, $update_elements);
    return $update_elements[0];
  }

}
