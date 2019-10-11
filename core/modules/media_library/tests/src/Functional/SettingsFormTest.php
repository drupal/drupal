<?php

namespace Drupal\Tests\media_library\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Media Library settings form.
 *
 * @coversDefaultClass \Drupal\media_library\Form\SettingsForm
 * @group media_library
 *
 * @todo Roll this test into
 *   https://www.drupal.org/project/drupal/issues/3087227
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_library'];

  /**
   * Tests the Media Library settings form.
   */
  public function testSettingsForm() {
    $account = $this->drupalCreateUser([
      'access administration pages',
      'administer media',
    ]);
    $this->drupalLogin($account);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/admin/config');
    $page->clickLink('Media Library settings');
    $page->checkField('Enable advanced UI');
    $page->pressButton('Save configuration');
    $assert_session->checkboxChecked('Enable advanced UI');
    $page->uncheckField('Enable advanced UI');
    $page->pressButton('Save configuration');
    $assert_session->checkboxNotChecked('Enable advanced UI');
  }

}
