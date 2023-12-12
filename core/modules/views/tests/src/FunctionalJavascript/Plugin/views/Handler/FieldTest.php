<?php

declare(strict_types=1);

namespace Drupal\Tests\views\FunctionalJavascript\Plugin\views\Handler;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the field handler UI.
 *
 * @group views
 */
class FieldTest extends WebDriverTestBase {
  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'views_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_body'];

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    ViewTestData::createTestViews(static::class, ['views_test_config']);

    // Disable automatic live preview to make the sequence of calls clearer.
    \Drupal::configFactory()->getEditable('views.settings')->set('ui.always_live_preview', FALSE)->save();

    $this->account = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($this->account);

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'body',
      'bundle' => 'page',
    ])->save();
  }

  public function testFormatterChanging() {
    $web_assert = $this->assertSession();
    $url = '/admin/structure/views/view/test_field_body';
    $this->drupalGet($url);

    $page = $this->getSession()->getPage();

    $page->clickLink('Body field');
    $web_assert->assertWaitOnAjaxRequest();

    $page->fillField('options[type]', 'text_trimmed');
    // Add a value to the trim_length setting.
    $web_assert->assertWaitOnAjaxRequest();
    $page->fillField('options[settings][trim_length]', '700');
    $apply_button = $page->find('css', '.views-ui-dialog button.button--primary');
    $this->assertNotEmpty($apply_button);
    $apply_button->press();
    $web_assert->assertWaitOnAjaxRequest();

    // Save the page.
    $save_button = $page->find('css', '#edit-actions-submit');
    $save_button->press();

    // Set the body field back to 'default' and test that the trim_length
    // settings are not in the config.
    $this->drupalGet($url);
    $page->clickLink('Body field');
    $web_assert->assertWaitOnAjaxRequest();

    $page->fillField('options[type]', 'text_default');
    $web_assert->assertWaitOnAjaxRequest();
    $apply_button = $page->find('css', '.views-ui-dialog button.button--primary');
    $apply_button->press();
    $web_assert->assertWaitOnAjaxRequest();

    // Save the page.
    $save_button = $page->find('css', '#edit-actions-submit');
    $save_button->press();

    $this->assertConfigSchemaByName('views.view.test_field_body');
  }

}
