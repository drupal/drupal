<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Tests\MenuLinkContentUITest.
 */

namespace Drupal\menu_link_content\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITest;

/**
 * Tests the menu link content UI.
 */
class MenuLinkContentUITest extends ContentTranslationUITest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'language',
    'content_translation',
    'menu_link_content',
    'menu_ui',
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Menu link content translation UI',
      'description' => 'Tests the basic menu link content translation UI.',
      'group' => 'Menu',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityTypeId = 'menu_link_content';
    $this->bundle = 'menu_link_content';
    $this->fieldName = 'title';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer menu'));
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($values, $langcode, $bundle_name = NULL) {
    $values['menu_name'] = 'tools';
    $values['route_name'] = 'menu_ui.overview_page';
    $values['title'] = 'Test title';

    return parent::createEntity($values, $langcode, $bundle_name);
  }

}
