<?php

declare(strict_types=1);

namespace Drupal\Tests\link\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests link field form states functionality.
 *
 * @group link
 */
class LinkFieldFormStatesTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'link',
    'node',
    'link_test_base_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer entity_test content',
    ]));
  }

  /**
   * @dataProvider linkFieldFormStatesData
   */
  public function testLinkFieldFormStates(string $uri, string $title): void {
    $this->drupalGet('entity_test/add');
    $session = $this->assertSession();
    $session->elementNotExists('css', '#edit-links-0-uri[required]');
    $session->elementNotExists('css', '#edit-links-0-title[required]');

    $page = $this->getSession()->getPage();

    if ($uri !== '') {
      $page->fillField('links[0][uri]', $uri);
      $session->elementNotExists('css', '#edit-links-0-uri[required]');
      $session->elementExists('css', '#edit-links-0-title[required]');
    }
    else {
      $page->fillField('links[0][title]', $title);
      $session->elementExists('css', '#edit-links-0-uri[required]');
      $session->elementNotExists('css', '#edit-links-0-title[required]');
    }
  }

  /**
   * Provides data for ::testLinkFieldJSFormStates.
   */
  public static function linkFieldFormStatesData() {
    return [
      'Fill uri, keep title empty' => [
        'https://example.com',
        '',
      ],
      'Fill title, keep uri empty' => [
        '',
        'https://example.com',
      ],
    ];
  }

}
