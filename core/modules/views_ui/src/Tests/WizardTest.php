<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\WizardTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Tests\Wizard\WizardTestBase;

/**
 * Tests the wizard.
 *
 * @see \Drupal\views\Plugin\views\display\DisplayPluginBase
 * @see \Drupal\views\Plugin\views\display\PathPluginBase
 * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
 */
class WizardTest extends WizardTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Views UI: Wizard',
      'description' => 'Tests the wizard.',
      'group' => 'Views Wizard',
    );
  }

  /**
   * Tests filling in the wizard with really long strings.
   */
  public function testWizardFieldLength() {
    $view = array();
    $view['label'] = $this->randomName(256);
    $view['id'] = strtolower($this->randomName(129));
    $view['page[create]'] = TRUE;
    $view['page[path]'] = $this->randomName(255);
    $view['page[title]'] = $this->randomName(256);
    $view['page[feed]'] = TRUE;
    $view['page[feed_properties][path]'] = $this->randomName(255);
    $view['block[create]'] = TRUE;
    $view['block[title]'] = $this->randomName(256);

    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    $this->assertText('Machine-readable name cannot be longer than 128 characters but is currently 129 characters long.');
    $this->assertText('Path cannot be longer than 254 characters but is currently 255 characters long.');
    $this->assertText('Page title cannot be longer than 255 characters but is currently 256 characters long.');
    $this->assertText('View name cannot be longer than 255 characters but is currently 256 characters long.');
    $this->assertText('Feed path cannot be longer than 254 characters but is currently 255 characters long.');
    $this->assertText('Block title cannot be longer than 255 characters but is currently 256 characters long.');

    $view['label'] = $this->randomName(255);
    $view['id'] = strtolower($this->randomName(128));
    $view['page[create]'] = TRUE;
    $view['page[path]'] = $this->randomName(254);
    $view['page[title]'] = $this->randomName(255);
    $view['page[feed]'] = TRUE;
    $view['page[feed_properties][path]'] = $this->randomName(254);
    $view['block[create]'] = TRUE;
    $view['block[title]'] = $this->randomName(255);

    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->assertUrl('admin/structure/views/view/' . $view['id'], array(), 'Make sure the view saving was successful and the browser got redirected to the edit page.');
    // Assert that the page title is correctly truncated.
    $this->assertText(views_ui_truncate($view['page[title]'], 32));
  }

}
