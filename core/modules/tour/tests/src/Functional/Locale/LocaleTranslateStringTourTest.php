<?php

namespace Drupal\Tests\tour\Functional\Locale;

use Drupal\Tests\tour\Functional\TourTestBase;

/**
 * Tests the Translate Interface tour.
 *
 * @group tour
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
  protected static $modules = ['locale', 'tour'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'translate interface',
      'access tour',
      'administer languages',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests locale tour tip availability.
   */
  public function testTranslateStringTourTips() {
    // Add another language so there are no missing form items.
    $edit = [];
    $edit['predefined_langcode'] = 'es';
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    $this->drupalGet('admin/config/regional/translate');
    $this->assertTourTips();
  }

}
