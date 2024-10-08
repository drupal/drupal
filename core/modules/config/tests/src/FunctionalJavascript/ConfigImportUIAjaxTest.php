<?php

declare(strict_types=1);

namespace Drupal\Tests\config\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the user interface for importing configuration.
 *
 * @group config
 */
class ConfigImportUIAjaxTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests an updated configuration object can be viewed more than once.
   */
  public function testImport(): void {
    $name = 'system.site';
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $user = $this->drupalCreateUser(['synchronize configuration']);
    $this->drupalLogin($user);
    $this->copyConfig($this->container->get('config.storage'), $this->container->get('config.storage.sync'));

    // Create updated configuration object.
    $new_site_name = 'Config import test ' . $this->randomString();
    $sync = $this->container->get('config.storage.sync');

    // Create updated configuration object.
    $config_data = $this->config('system.site')->get();
    $config_data['name'] = $new_site_name;
    $sync->write('system.site', $config_data);
    $this->assertTrue($sync->exists($name), $name . ' found.');

    // Verify that system.site appears as ready to import.
    $this->drupalGet('admin/config/development/configuration');
    $this->assertSession()->responseContains('<td>' . $name);
    $this->assertSession()->buttonExists('Import all');

    // Click the dropbutton to show the differences in a modal and close it.
    $page->find('css', '.dropbutton-action')->click();
    $assert_session->waitForElementVisible('css', '.ui-dialog');
    $assert_session->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-content');
    $page->pressButton('Close');
    $assert_session->assertNoElementAfterWait('css', '.ui-dialog');

    // Do this again to make sure no JavaScript errors occur on revisits.
    $page->find('css', '.dropbutton-action')->click();
    $assert_session->waitForElementVisible('css', '.ui-dialog');
    $assert_session->assertVisibleInViewport('css', '.ui-dialog .ui-dialog-content');
    $page->pressButton('Close');
    $assert_session->assertNoElementAfterWait('css', '.ui-dialog');
  }

}
