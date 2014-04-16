<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocaleTranslateStringTourTest.
 */

namespace Drupal\locale\Tests;

use Drupal\tour\Tests\TourTestBase;

/**
 * Tests tour functionality.
 */
class LocaleTranslateStringTourTest extends TourTestBase {

  /**
   * An admin user with administrative permissions to translate.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'tour');

  public static function getInfo() {
    return array(
      'name' => 'Translate Interface tour tests',
      'description' => 'Tests the Translate Interface tour.',
      'group' => 'Tour',
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array('translate interface', 'access tour', 'administer languages'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests locale tour tip availability.
   */
  public function testTranslateStringTourTips() {
    // Add Another Language so there is no missing form items
    $edit = array();
    $edit['predefined_langcode'] = 'es';
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    $this->drupalGet('admin/config/regional/translate');
    $this->assertTourTips();
  }

}
