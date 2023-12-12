<?php

declare(strict_types=1);

namespace Drupal\Tests\views\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests that the redirects work with Ajax enabled views.
 *
 * @group views
 */
class RedirectAjaxTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable AJAX on the /admin/content View.
    \Drupal::configFactory()->getEditable('views.view.content')
      ->set('display.default.display_options.use_ajax', TRUE)
      ->save();

    $this->createContentType(['type' => 'article']);
    $this->createNode(['title' => 'Tiny paws and playful mews, kittens bring joy in every hue', 'type' => 'article']);

    $user = $this->drupalCreateUser([
      'access content overview',
      'administer nodes',
      'bypass node access',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Ensures that redirects work with ajax.
   */
  public function testRedirectWithAjax() {
    $this->drupalGet('admin/content');
    $original_url = $this->getSession()->getCurrentUrl();

    $this->assertSession()->pageTextContains('Tiny paws and playful mews, kittens bring joy in every hue');

    $this->submitForm(['title' => 'Kittens'], 'Filter');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('Tiny paws and playful mews, kittens bring joy in every hue');
    $this->getSession()->getPage()->find('css', '.dropbutton-toggle button')->click();
    $this->clickLink('Delete');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertEquals('Are you sure you want to delete the content item Tiny paws and playful mews, kittens bring joy in every hue?', $this->assertSession()->waitForElement('css', '.ui-dialog-title')->getText());
    $this->getSession()->getPage()->find('css', '.ui-dialog-buttonset')->pressButton('Delete');

    $this->assertSession()->pageTextContains('The Article Tiny paws and playful mews, kittens bring joy in every hue has been deleted.');
    $this->assertStringStartsWith($original_url, $this->getSession()->getCurrentUrl());
    $this->assertSession()->responseContains('core/modules/views/css/views.module.css');
  }

}
