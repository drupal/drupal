<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\language\Traits\LanguageTestTrait;

/**
 * Tests Menu UI and Content Translation integration for content entities.
 *
 * @group menu_ui
 */
class MenuUiContentTranslationTest extends BrowserTestBase {

  use LanguageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'language',
    'content_translation',
    'menu_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place menu block and local tasks block.
    $this->drupalPlaceBlock('system_menu_block:main');
    $this->drupalPlaceBlock('local_tasks_block');

    // Create a 'page' content type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);

    // Add a second language.
    static::createLanguageFromLangcode('de');

    // Create an account and login.
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      'create page content',
      'edit any page content',
      'delete any page content',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
      'administer content types',
      'administer menu',
    ]);
    $this->drupalLogin($user);

    // Enable translation for page nodes and menu link content.
    static::enableBundleTranslation('node', 'page');
    static::enableBundleTranslation('menu_link_content', 'menu_link_content');
  }

  /**
   * Gets a content entity object by title.
   *
   * @param string $entity_type_id
   *   Id of content entity type of content entity to load.
   * @param string $title
   *   Title of content entity to load.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   First found content entity with given title.
   */
  protected function getContentEntityByTitle($entity_type_id, $title) {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $storage = $entity_type_manager->getStorage($entity_type_id);
    $storage->resetCache();
    $entities = $storage->loadByProperties([
      'title' => $title,
    ]);
    return reset($entities);
  }

  /**
   * Provides test data sets for testChangeContentToPseudoLanguage().
   *
   * @return array
   *   Data sets to test keyed by data set label.
   */
  public static function provideChangeContentToPseudoLanguageData() {
    return [
      'und' => ['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED],
      'zxx' => ['langcode' => LanguageInterface::LANGCODE_NOT_APPLICABLE],
    ];
  }

  /**
   * Tests changing content with menu link from language to pseudo language.
   *
   * @param string $langcode
   *   Language code of pseudo-language to change content language to.
   *   Either \Drupal\Core\LanguageInterface::LANGCODE_NOT_SPECIFIED or
   *   \Drupal\Core\LanguageInterface::LANGCODE_NOT_APPLICABLE.
   *
   * @dataProvider provideChangeContentToPseudoLanguageData
   */
  public function testChangeContentToPseudoLanguage($langcode): void {
    $node_title = 'Test node';
    $menu_link_title_en = 'Test menu link EN';
    $menu_link_title_pseudo = 'Test menu link PSEUDO';

    // Create a page node in English.
    $this->drupalGet('node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $node_title,
      'menu[enabled]' => 1,
      'menu[title]' => $menu_link_title_en,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that node exists and node language is English.
    $node = $this->getContentEntityByTitle('node', $node_title);
    $this->assertTrue(is_object($node));
    $this->assertTrue($node->language()->getId() == 'en');

    // Assert that menu link exists and menu link language is English.
    $menu_link = $this->getContentEntityByTitle('menu_link_content', $menu_link_title_en);
    $this->assertTrue(is_object($menu_link));
    $this->assertTrue($menu_link->language()->getId() == 'en');
    $this->assertTrue($menu_link->hasTranslation('en'));

    // Assert that menu link is visible with initial title.
    $this->assertSession()->linkExists($menu_link_title_en);

    // Change language of page node and title of its menu link.
    $this->clickLink('Edit');
    $edit = [
      'langcode[0][value]' => $langcode,
      'menu[title]' => $menu_link_title_pseudo,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that node exists and node language is target language.
    $node = $this->getContentEntityByTitle('node', $node_title);
    $this->assertTrue(is_object($node));
    $this->assertTrue($node->language()->getId() == $langcode);

    // Assert that menu link exists and menu link language is target language.
    $menu_link = $this->getContentEntityByTitle('menu_link_content', $menu_link_title_pseudo);
    $this->assertTrue(is_object($menu_link));
    $this->assertTrue($menu_link->language()->getId() == $langcode);
    $this->assertFalse($menu_link->hasTranslation('en'));

    // Assert that menu link is visible with updated title.
    $this->assertSession()->linkExists($menu_link_title_pseudo);
  }

}
