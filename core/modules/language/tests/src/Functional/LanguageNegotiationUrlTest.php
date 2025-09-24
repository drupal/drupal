<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl.
 */
#[CoversClass(LanguageNegotiationUrl::class)]
#[Group('language')]
#[RunTestsInSeparateProcesses]
class LanguageNegotiationUrlTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'node',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article']);
    }

    $this->user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'view the administration theme',
      'administer nodes',
      'create article content',
      'create url aliases',
    ]);
    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm(['predefined_langcode' => 'de'], 'Add language');
  }

  /**
   * Tests domain.
   *
   * @legacy-covers ::processInbound
   */
  public function testDomain(): void {
    // Check if paths that contain language prefixes can be reached when
    // language is taken from the domain.
    $edit = [
      'language_negotiation_url_part' => 'domain',
      'prefix[en]' => 'eng',
      'prefix[de]' => 'de',
      'domain[en]' => $_SERVER['HTTP_HOST'],
      'domain[de]' => "de.$_SERVER[HTTP_HOST]",
    ];
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->submitForm($edit, 'Save configuration');

    $nodeValues = [
      'title[0][value]' => 'Test',
      'path[0][alias]' => '/eng/test',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($nodeValues, 'Save');
    $this->assertSession()->statusCodeEquals(200);
  }

}
