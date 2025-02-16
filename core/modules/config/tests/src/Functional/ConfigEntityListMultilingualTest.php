<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

// cspell:ignore antilop

/**
 * Tests the listing of configuration entities in a multilingual scenario.
 *
 * @group config
 */
class ConfigEntityListMultilingualTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test', 'language', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Delete the override config_test entity. It is not required by this test.
    \Drupal::entityTypeManager()->getStorage('config_test')->load('override')->delete();
    ConfigurableLanguage::createFromLangcode('hu')->save();

    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests the listing UI with different language scenarios.
   */
  public function testListUI(): void {
    // Log in as an administrative user to access the full menu trail.
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
    ]));

    // Get the list page.
    $this->drupalGet('admin/structure/config_test');
    $this->assertSession()->linkByHrefExists('admin/structure/config_test/manage/dotted.default');

    // Add a new entity using the action link.
    $this->clickLink('Add test configuration');
    $edit = [
      'label' => 'Antilop',
      'id' => 'antilop',
      'langcode' => 'hu',
    ];
    $this->submitForm($edit, 'Save');
    // Ensure that operations for editing the Hungarian entity appear in
    // English.
    $this->assertSession()->linkByHrefExists('admin/structure/config_test/manage/antilop');

    // Get the list page in Hungarian and assert Hungarian admin links
    // regardless of language of config entities.
    $this->drupalGet('hu/admin/structure/config_test');
    $this->assertSession()->linkByHrefExists('hu/admin/structure/config_test/manage/dotted.default');
    $this->assertSession()->linkByHrefExists('hu/admin/structure/config_test/manage/antilop');
  }

}
