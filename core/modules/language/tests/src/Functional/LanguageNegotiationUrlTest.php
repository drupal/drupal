<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * @coversDefaultClass \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl
 * @group language
 */
class LanguageNegotiationUrlTest extends BrowserTestBase {

  use StringTranslationTrait;

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

    $this->drupalPostForm('admin/config/regional/language/add', ['predefined_langcode' => 'de'], $this->t('Add language'));
  }

  /**
   * @covers ::processInbound
   */
  public function testDomain() {
    // Check if paths that contain language prefixes can be reached when
    // language is taken from the domain.
    $edit = [
      'language_negotiation_url_part' => 'domain',
      'prefix[en]' => 'eng',
      'prefix[de]' => 'de',
      'domain[en]' => $_SERVER['HTTP_HOST'],
      'domain[de]' => "de.$_SERVER[HTTP_HOST]",
    ];
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, $this->t('Save configuration'));

    $nodeValues = [
      'title[0][value]' => 'Test',
      'path[0][alias]' => '/eng/test',
    ];
    $this->drupalPostForm('node/add/article', $nodeValues, $this->t('Save'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
