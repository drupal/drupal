<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Functional;

use Drupal\Tests\BrowserTestBase;
use PhpTuf\ComposerStager\API\Exception\LogicException;
use PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface;

/**
 * Tests the Package Manager settings form.
 *
 * @group package_manager
 */
final class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['package_manager'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that executable paths can be configured through the settings form.
   */
  public function testSettingsForm(): void {
    $assert_session = $this->assertSession();
    $account = $this->drupalCreateUser(['administer software updates']);
    $this->drupalLogin($account);

    /** @var \PhpTuf\ComposerStager\API\Finder\Service\ExecutableFinderInterface $executable_finder */
    $executable_finder = \Drupal::service(ExecutableFinderInterface::class);
    try {
      $composer_path = $executable_finder->find('composer');
      $rsync_path = $executable_finder->find('rsync');
    }
    catch (LogicException) {
      $this->markTestSkipped('This test requires Composer and rsync to be available in the PATH.');
    }

    $this->drupalGet('/admin/config/system/package-manager');
    $assert_session->statusCodeEquals(200);
    // Submit the settings form with the detected paths, with whitespace added
    // to test that it is trimmed out.
    $this->submitForm([
      'composer' => "$composer_path  ",
      'rsync' => " $rsync_path",
    ], 'Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved.');

    // Verify the paths were saved in config.
    $config = $this->config('package_manager.settings');
    $this->assertSame($composer_path, $config->get('executables.composer'));
    $this->assertSame($rsync_path, $config->get('executables.rsync'));

    // Verify the paths are shown in the form.
    $this->drupalGet('/admin/config/system/package-manager');
    $assert_session->fieldValueEquals('composer', $composer_path);
    $assert_session->fieldValueEquals('rsync', $rsync_path);

    // Ensure that the executable paths are confirmed to be executable.
    $this->submitForm([
      'composer' => 'rm -rf /',
      'rsync' => 'cat /etc/passwd',
    ], 'Save configuration');
    $assert_session->statusMessageContains('The file could not be found.', 'error');
    $assert_session->statusMessageContains('"cat /etc/passwd" is not an executable file.', 'error');
    $this->assertTrue($assert_session->fieldExists('composer')->hasClass('error'));
    $this->assertTrue($assert_session->fieldExists('rsync')->hasClass('error'));
  }

}
