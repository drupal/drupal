<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\file\Functional\FileFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests maintenance message during an AJAX call.
 *
 * @group Ajax
 */
class AjaxMaintenanceModeTest extends WebDriverTestBase {

  use FieldUiTestTrait;
  use FileFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ajax_test'];

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
      'access administration pages',
      'administer site configuration',
      'access site in maintenance mode',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests maintenance message only appears once on an AJAX call.
   */
  public function testAjaxCallMaintenanceMode(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    \Drupal::state()->set('system.maintenance_mode', TRUE);

    $this->drupalGet('ajax-test/insert-inline-wrapper');
    $assert_session->pageTextContains('Target inline');
    $page->clickLink('Link html pre-wrapped-div');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContainsOnce('Operating in maintenance mode');
  }

}
