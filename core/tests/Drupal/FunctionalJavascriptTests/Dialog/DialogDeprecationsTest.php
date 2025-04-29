<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\Dialog;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests jQuery events deprecations.
 *
 * @group dialog
 */
class DialogDeprecationsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'js_deprecation_test',
  ];

  /**
   * Tests that the deprecation events are triggered.
   */
  #[IgnoreDeprecations]
  public function testDialogDeprecations(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer blocks']));
    $this->drupalGet('/admin/structure/block');
    $assert_session = $this->assertSession();

    $button = $assert_session->waitForElement('css', '[data-drupal-selector="edit-blocks-region-sidebar-first-title"]');
    $this->assertNotNull($button);
    $button->click();

    $this->assertNotNull($assert_session->waitForElement('css', '.ui-dialog-content'));
    $this->getSession()->executeScript("window.jQuery('.ui-dialog-content').trigger('dialogButtonsChange');");
    $this->expectDeprecation('Javascript Deprecation: jQuery event dialogButtonsChange is deprecated in 11.2.0 and is removed from Drupal:12.0.0. See https://www.drupal.org/node/3464202');
  }

}
