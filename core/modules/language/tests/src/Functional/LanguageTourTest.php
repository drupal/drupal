<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\tour\Functional\TourTestBase;

/**
 * Tests tour functionality.
 *
 * @group tour
 */
class LanguageTourTest extends TourTestBase {

  /**
   * An admin user with administrative permissions for views.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'language', 'tour'];

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
      'administer languages',
      'access tour',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests language tour tip availability.
   */
  public function testLanguageTour() {
    $this->drupalGet('admin/config/regional/language');
    $this->assertTourTips();
  }

  /**
   * Go to add language page and check the tour tooltips.
   */
  public function testLanguageAddTour() {
    $this->drupalGet('admin/config/regional/language/add');
    $this->assertTourTips();
  }

  /**
   * Go to edit language page and check the tour tooltips.
   */
  public function testLanguageEditTour() {
    $this->drupalGet('admin/config/regional/language/edit/en');
    $this->assertTourTips();
  }

}
