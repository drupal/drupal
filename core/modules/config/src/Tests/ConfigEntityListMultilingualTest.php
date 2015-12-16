<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigEntityListMultilingualTest.
 */

namespace Drupal\config\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the listing of configuration entities in a multilingual scenario.
 *
 * @group config
 */
class ConfigEntityListMultilingualTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test', 'language', 'block');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Delete the override config_test entity. It is not required by this test.
    \Drupal::entityManager()->getStorage('config_test')->load('override')->delete();
    ConfigurableLanguage::createFromLangcode('hu')->save();

    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests the listing UI with different language scenarios.
   */
  function testListUI() {
    // Log in as an administrative user to access the full menu trail.
    $this->drupalLogin($this->drupalCreateUser(array('access administration pages', 'administer site configuration')));

    // Get the list page.
    $this->drupalGet('admin/structure/config_test');
    $this->assertLinkByHref('admin/structure/config_test/manage/dotted.default');

    // Add a new entity using the action link.
    $this->clickLink('Add test configuration');
    $edit = array(
      'label' => 'Antilop',
      'id' => 'antilop',
      'langcode' => 'hu',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Ensure that operations for editing the Hungarian entity appear in English.
    $this->assertLinkByHref('admin/structure/config_test/manage/antilop');

    // Get the list page in Hungarian and assert Hungarian admin links
    // regardless of language of config entities.
    $this->drupalGet('hu/admin/structure/config_test');
    $this->assertLinkByHref('hu/admin/structure/config_test/manage/dotted.default');
    $this->assertLinkByHref('hu/admin/structure/config_test/manage/antilop');
  }

}
