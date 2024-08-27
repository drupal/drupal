<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

// cspell:ignore publi publié

/**
 * Functional tests for the language switching feature.
 *
 * @group language
 */
class LanguageSwitchingTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'locale',
    'locale_test',
    'language',
    'block',
    'language_test',
    'menu_ui',
    'node',
  ];

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $admin_user = $this->drupalCreateUser([
      'administer blocks',
      'administer languages',
      'administer site configuration',
      'access administration pages',
      'access content',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Functional tests for the language switcher block.
   */
  public function testLanguageBlock(): void {
    // Add language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Set the native language name.
    $this->saveNativeLanguageName('fr', 'français');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Enable the language switching block.
    $block = $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, [
      'id' => 'test_language_block',
      // Ensure a 2-byte UTF-8 sequence is in the tested output.
      'label' => $this->randomMachineName(8) . '×',
    ]);

    $this->doTestLanguageBlockAuthenticated($block->label());
    $this->doTestHomePageLinks($block->label());
    $this->doTestLanguageBlockAnonymous($block->label());
    $this->doTestLanguageBlock404($block->label(), 'system/404');

    // Test 404s with big_pipe where the behavior is different for logged-in
    // users.
    \Drupal::service('module_installer')->install(['big_pipe']);
    $this->rebuildAll();
    $this->doTestLanguageBlock404($block->label(), 'system/404');
    $this->drupalLogin($this->drupalCreateUser());
    // @todo This is testing the current behavior with the big_pipe module
    //   enabled. This behavior is a bug will be fixed in
    //   https://www.drupal.org/project/drupal/issues/3349201.
    $this->doTestLanguageBlock404($block->label(), '<front>');
  }

  /**
   * The home page link should be "/" or "/{language_prefix}".
   *
   * @param string $block_label
   *   The label of the language switching block.
   *
   * @see self::testLanguageBlock()
   */
  protected function doTestHomePageLinks($block_label) {
    // Create a node and set as home page.
    $this->createHomePage();
    // Go to home page.
    $this->DrupalGet('<front>');
    // The language switcher block should display.
    $this->assertSession()->pageTextContains($block_label);
    // Assert that each list item and anchor element has the appropriate data-
    // attributes.
    $language_switchers = $this->xpath('//div[@id=:id]/ul/li', [':id' => 'block-test-language-block']);
    $list_items = [];
    $anchors = [];
    $labels = [];
    foreach ($language_switchers as $list_item) {
      $list_items[] = [
        'hreflang' => $list_item->getAttribute('hreflang'),
        'data-drupal-link-system-path' => $list_item->getAttribute('data-drupal-link-system-path'),
      ];

      $link = $list_item->find('xpath', 'a');
      $anchors[] = [
        'hreflang' => $link->getAttribute('hreflang'),
        'data-drupal-link-system-path' => $link->getAttribute('data-drupal-link-system-path'),
        'href' => $link->getAttribute('href'),
      ];
      $labels[] = $link->getText();
    }
    $expected_list_items = [
      0 => [
        'hreflang' => 'en',
        'data-drupal-link-system-path' => '<front>',
      ],
      1 => [
        'hreflang' => 'fr',
        'data-drupal-link-system-path' => '<front>',
      ],
    ];
    $this->assertSame($expected_list_items, $list_items, 'The list items have the correct attributes that will contain the correct home page links.');
    $expected_anchors = [
      0 => [
        'hreflang' => 'en',
        'data-drupal-link-system-path' => '<front>',
        'href' => Url::fromRoute('<front>')->toString(),
      ],
      1 => [
        'hreflang' => 'fr',
        'data-drupal-link-system-path' => '<front>',
        'href' => Url::fromRoute('<front>')->toString() . 'fr',
      ],
    ];
    $this->assertSame($expected_anchors, $anchors, 'The anchors have the correct attributes that will link to the correct home page in that language.');
    $this->assertSame(['English', 'français'], $labels, 'The language links labels are in their own language on the language switcher block.');
  }

  /**
   * For authenticated users, the "active" class is set by JavaScript.
   *
   * @param string $block_label
   *   The label of the language switching block.
   *
   * @see self::testLanguageBlock()
   */
  protected function doTestLanguageBlockAuthenticated($block_label) {
    // Assert that the language switching block is displayed on the frontpage.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($block_label);

    // Assert that each list item and anchor element has the appropriate data-
    // attributes.
    $language_switchers = $this->xpath('//div[@id=:id]/ul/li', [':id' => 'block-test-language-block']);
    $list_items = [];
    $anchors = [];
    $labels = [];
    foreach ($language_switchers as $list_item) {
      $list_items[] = [
        'hreflang' => $list_item->getAttribute('hreflang'),
        'data-drupal-link-system-path' => $list_item->getAttribute('data-drupal-link-system-path'),
      ];

      $link = $list_item->find('xpath', 'a');
      $anchors[] = [
        'hreflang' => $link->getAttribute('hreflang'),
        'data-drupal-link-system-path' => $link->getAttribute('data-drupal-link-system-path'),
      ];
      $labels[] = $link->getText();
    }
    $expected_list_items = [
      0 => ['hreflang' => 'en', 'data-drupal-link-system-path' => 'user/2'],
      1 => ['hreflang' => 'fr', 'data-drupal-link-system-path' => 'user/2'],
    ];
    $this->assertSame($expected_list_items, $list_items, 'The list items have the correct attributes that will allow the drupal.active-link library to mark them as active.');
    $expected_anchors = [
      0 => ['hreflang' => 'en', 'data-drupal-link-system-path' => 'user/2'],
      1 => ['hreflang' => 'fr', 'data-drupal-link-system-path' => 'user/2'],
    ];
    $this->assertSame($expected_anchors, $anchors, 'The anchors have the correct attributes that will allow the drupal.active-link library to mark them as active.');
    $settings = $this->getDrupalSettings();
    $this->assertSame('user/2', $settings['path']['currentPath'], 'drupalSettings.path.currentPath is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertFalse($settings['path']['isFront'], 'drupalSettings.path.isFront is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertSame('en', $settings['path']['currentLanguage'], 'drupalSettings.path.currentLanguage is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertSame(['English', 'français'], $labels, 'The language links labels are in their own language on the language switcher block.');
  }

  /**
   * For anonymous users, the "active" class is set by PHP.
   *
   * @param string $block_label
   *   The label of the language switching block.
   *
   * @see self::testLanguageBlock()
   */
  protected function doTestLanguageBlockAnonymous($block_label) {
    $this->drupalLogout();

    // Assert that the language switching block is displayed on the frontpage
    // and ensure that the active class is added when query params are present.
    $this->drupalGet('', ['query' => ['foo' => 'bar']]);
    $this->assertSession()->pageTextContains($block_label);

    // Assert that only the current language is marked as active.
    $language_switchers = $this->xpath('//div[@id=:id]/ul/li', [':id' => 'block-test-language-block']);
    $links = [
      'active' => [],
      'inactive' => [],
    ];
    $anchors = [
      'active' => [],
      'inactive' => [],
    ];
    $labels = [];
    foreach ($language_switchers as $list_item) {
      $langcode = $list_item->getAttribute('hreflang');
      if ($list_item->hasClass('is-active')) {
        $links['active'][] = $langcode;
      }
      else {
        $links['inactive'][] = $langcode;
      }

      $link = $list_item->find('xpath', 'a');
      $anchor_classes = explode(" ", $link->getAttribute('class'));
      if (in_array('is-active', $anchor_classes)) {
        $anchors['active'][] = $langcode;
      }
      else {
        $anchors['inactive'][] = $langcode;
      }
      $labels[] = $link->getText();
    }
    $this->assertSame(['active' => ['en'], 'inactive' => ['fr']], $links, 'Only the current language list item is marked as active on the language switcher block.');
    $this->assertSame(['active' => ['en'], 'inactive' => ['fr']], $anchors, 'Only the current language anchor is marked as active on the language switcher block.');
    $this->assertSame(['English', 'français'], $labels, 'The language links labels are in their own language on the language switcher block.');
  }

  /**
   * Tests the language switcher block on 404 pages.
   *
   * @param string $block_label
   *   The label of the language switching block.
   * @param string $system_path
   *   The expected system path for the links in the language switcher.
   *
   * @see self::testLanguageBlock()
   */
  protected function doTestLanguageBlock404(string $block_label, string $system_path) {
    $this->drupalGet('does-not-exist-' . $this->randomMachineName());
    $this->assertSession()->pageTextContains($block_label);

    // Assert that each list item and anchor element has the appropriate data-
    // attributes.
    $language_switchers = $this->xpath('//div[@id=:id]/ul/li', [':id' => 'block-test-language-block']);
    $list_items = [];
    $anchors = [];
    $labels = [];
    foreach ($language_switchers as $list_item) {
      $list_items[] = [
        'hreflang' => $list_item->getAttribute('hreflang'),
        'data-drupal-link-system-path' => $list_item->getAttribute('data-drupal-link-system-path'),
      ];

      $link = $list_item->find('xpath', 'a');
      $anchors[] = [
        'hreflang' => $link->getAttribute('hreflang'),
        'data-drupal-link-system-path' => $link->getAttribute('data-drupal-link-system-path'),
      ];
      $labels[] = $link->getText();
    }
    $expected_list_items = [
      0 => ['hreflang' => 'en', 'data-drupal-link-system-path' => $system_path],
      1 => ['hreflang' => 'fr', 'data-drupal-link-system-path' => $system_path],
    ];
    $this->assertSame($expected_list_items, $list_items, 'The list items have the correct attributes that will allow the drupal.active-link library to mark them as active.');
    $expected_anchors = [
      0 => ['hreflang' => 'en', 'data-drupal-link-system-path' => $system_path],
      1 => ['hreflang' => 'fr', 'data-drupal-link-system-path' => $system_path],
    ];
    $this->assertSame($expected_anchors, $anchors, 'The anchors have the correct attributes that will allow the drupal.active-link library to mark them as active.');
    $this->assertSame(['English', 'français'], $labels, 'The language links labels are in their own language on the language switcher block.');
  }

  /**
   * Tests language switcher links for domain based negotiation.
   */
  public function testLanguageBlockWithDomain(): void {
    // Add the Italian language.
    ConfigurableLanguage::createFromLangcode('it')->save();

    // Rebuild the container so that the new language is picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();

    $languages = $this->container->get('language_manager')->getLanguages();

    // Enable browser and URL language detection.
    $edit = [
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-url]' => -10,
    ];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Do not allow blank domain.
    $edit = [
      'language_negotiation_url_part' => LanguageNegotiationUrl::CONFIG_DOMAIN,
      'domain[en]' => '',
    ];
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusMessageContains('The domain may not be left blank for English', 'error');

    // Change the domain for the Italian language.
    $edit = [
      'language_negotiation_url_part' => LanguageNegotiationUrl::CONFIG_DOMAIN,
      'domain[en]' => \Drupal::request()->getHost(),
      'domain[it]' => 'it.example.com',
    ];
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusMessageContains('The configuration options have been saved', 'status');

    // Enable the language switcher block.
    $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, ['id' => 'test_language_block']);

    $this->drupalGet('');

    /** @var \Drupal\Core\Routing\UrlGenerator $generator */
    $generator = $this->container->get('url_generator');

    // Verify the English URL is correct
    $english_url = $generator->generateFromRoute('entity.user.canonical', ['user' => 2], ['language' => $languages['en']]);
    $this->assertSession()->elementAttributeContains('xpath', '//div[@id="block-test-language-block"]/ul/li/a[@hreflang="en"]', 'href', $english_url);

    // Verify the Italian URL is correct
    $italian_url = $generator->generateFromRoute('entity.user.canonical', ['user' => 2], ['language' => $languages['it']]);
    $this->assertSession()->elementAttributeContains('xpath', '//div[@id="block-test-language-block"]/ul/li/a[@hreflang="it"]', 'href', $italian_url);
  }

  /**
   * Tests active class on links when switching languages.
   */
  public function testLanguageLinkActiveClass(): void {
    // Add language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    $this->doTestLanguageLinkActiveClassAuthenticated();
    $this->doTestLanguageLinkActiveClassAnonymous();
  }

  /**
   * Check the path-admin class, as same as on default language.
   */
  public function testLanguageBodyClass(): void {
    // Add language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Check if the default (English) admin/config page has the right class.
    $this->drupalGet('admin/config');
    $this->assertSession()->elementAttributeContains('xpath', '//body', 'class', 'path-admin');

    // Check if the French admin/config page has the right class.
    $this->drupalGet('fr/admin/config');
    $this->assertSession()->elementAttributeContains('xpath', '//body', 'class', 'path-admin');

    // The testing profile sets the user/login page as the frontpage. That
    // redirects authenticated users to their profile page, so check with an
    // anonymous user instead.
    $this->drupalLogout();

    // Check if the default (English) frontpage has the right class.
    $this->drupalGet('<front>');
    $this->assertSession()->elementAttributeContains('xpath', '//body', 'class', 'path-frontpage');

    // Check if the French frontpage has the right class.
    $this->drupalGet('fr');
    $this->assertSession()->elementAttributeContains('xpath', '//body', 'class', 'path-frontpage');
  }

  /**
   * For authenticated users, the "active" class is set by JavaScript.
   *
   * @see self::testLanguageLinkActiveClass()
   */
  protected function doTestLanguageLinkActiveClassAuthenticated() {
    $function_name = '#type link';
    $path = 'language_test/type-link-active-class';

    // Test links generated by the link generator on an English page.
    $current_language = 'English';
    $this->drupalGet($path);

    // Language code 'none' link should be active.
    $this->assertSession()->elementAttributeContains('named', ['id', 'no_lang_link'], 'data-drupal-link-system-path', $path);

    // Language code 'en' link should be active.
    $this->assertSession()->elementAttributeContains('named', ['id', 'en_link'], 'hreflang', 'en');
    $this->assertSession()->elementAttributeContains('named', ['id', 'en_link'], 'data-drupal-link-system-path', $path);

    // Language code 'fr' link should not be active.
    $this->assertSession()->elementAttributeContains('named', ['id', 'fr_link'], 'hreflang', 'fr');
    $this->assertSession()->elementAttributeContains('named', ['id', 'fr_link'], 'data-drupal-link-system-path', $path);

    // Verify that drupalSettings contains the correct values.
    $settings = $this->getDrupalSettings();
    $this->assertSame($path, $settings['path']['currentPath'], 'drupalSettings.path.currentPath is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertFalse($settings['path']['isFront'], 'drupalSettings.path.isFront is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertSame('en', $settings['path']['currentLanguage'], 'drupalSettings.path.currentLanguage is set correctly to allow drupal.active-link to mark the correct links as active.');

    // Test links generated by the link generator on a French page.
    $current_language = 'French';
    $this->drupalGet('fr/language_test/type-link-active-class');

    // Language code 'none' link should be active.
    $this->assertSession()->elementAttributeContains('named', ['id', 'no_lang_link'], 'data-drupal-link-system-path', $path);

    // Language code 'en' link should not be active.
    $this->assertSession()->elementAttributeContains('named', ['id', 'en_link'], 'hreflang', 'en');
    $this->assertSession()->elementAttributeContains('named', ['id', 'en_link'], 'data-drupal-link-system-path', $path);

    // Language code 'fr' link should be active.
    $this->assertSession()->elementAttributeContains('named', ['id', 'fr_link'], 'hreflang', 'fr');
    $this->assertSession()->elementAttributeContains('named', ['id', 'fr_link'], 'data-drupal-link-system-path', $path);

    // Verify that drupalSettings contains the correct values.
    $settings = $this->getDrupalSettings();
    $this->assertSame($path, $settings['path']['currentPath'], 'drupalSettings.path.currentPath is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertFalse($settings['path']['isFront'], 'drupalSettings.path.isFront is set correctly to allow drupal.active-link to mark the correct links as active.');
    $this->assertSame('fr', $settings['path']['currentLanguage'], 'drupalSettings.path.currentLanguage is set correctly to allow drupal.active-link to mark the correct links as active.');
  }

  /**
   * For anonymous users, the "active" class is set by PHP.
   *
   * @see self::testLanguageLinkActiveClass()
   */
  protected function doTestLanguageLinkActiveClassAnonymous() {
    $function_name = '#type link';

    $this->drupalLogout();

    // Test links generated by the link generator on an English page.
    $current_language = 'English';
    $this->drupalGet('language_test/type-link-active-class');

    // Language code 'none' link should be active.
    $this->assertSession()->elementExists('xpath', "//a[@id = 'no_lang_link' and contains(@class, 'is-active')]");

    // Language code 'en' link should be active.
    $this->assertSession()->elementExists('xpath', "//a[@id = 'en_link' and contains(@class, 'is-active')]");

    // Language code 'fr' link should not be active.
    $this->assertSession()->elementExists('xpath', "//a[@id = 'fr_link' and not(contains(@class, 'is-active'))]");

    // Test links generated by the link generator on a French page.
    $current_language = 'French';
    $this->drupalGet('fr/language_test/type-link-active-class');

    // Language code 'none' link should be active.
    $this->assertSession()->elementExists('xpath', "//a[@id = 'no_lang_link' and contains(@class, 'is-active')]");

    // Language code 'en' link should not be active.
    $this->assertSession()->elementExists('xpath', "//a[@id = 'en_link' and not(contains(@class, 'is-active'))]");

    // Language code 'fr' link should be active.
    $this->assertSession()->elementExists('xpath', "//a[@id = 'fr_link' and contains(@class, 'is-active')]");
  }

  /**
   * Tests language switcher links for session based negotiation.
   */
  public function testLanguageSessionSwitchLinks(): void {
    // Add language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // Enable session language detection and selection.
    $edit = [
      'language_interface[enabled][language-url]' => FALSE,
      'language_interface[enabled][language-session]' => TRUE,
    ];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Enable the language switching block.
    $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE, [
      'id' => 'test_language_block',
    ]);

    // Enable the main menu block.
    $this->drupalPlaceBlock('system_menu_block:main', [
      'id' => 'test_menu',
    ]);

    // Add a link to the homepage.
    $link = MenuLinkContent::create([
      'title' => 'Home',
      'menu_name' => 'main',
      'bundle' => 'menu_link_content',
      'link' => [['uri' => 'entity:user/2']],
    ]);
    $link->save();

    // Go to the homepage.
    $this->drupalGet('');
    // Click on the French link.
    $this->clickLink('French');
    // There should be a query parameter to set the session language.
    $this->assertSession()->addressEquals('user/2?language=fr');
    // Click on the 'Home' Link.
    $this->clickLink('Home');
    // There should be no query parameter.
    $this->assertSession()->addressEquals('user/2');
    // Click on the French link.
    $this->clickLink('French');
    // There should be no query parameter.
    $this->assertSession()->addressEquals('user/2');
  }

  /**
   * Test that the language switching block does not expose restricted paths.
   */
  public function testRestrictedPaths(): void {
    $entity_type_manager = \Drupal::entityTypeManager();

    // Add the French language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Enable URL language detection and selection.
    $this->config('language.types')
      ->set('negotiation.language_interface.enabled.language-url', 1)
      ->save();

    // Enable the language switching block.
    $block = $this->drupalPlaceBlock('language_block:' . LanguageInterface::TYPE_INTERFACE);

    // Create a node type and make it translatable.
    $entity_type_manager->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'Page',
      ])
      ->save();

    // Create a published node with an unpublished translation.
    $node = $entity_type_manager->getStorage('node')
      ->create([
        'type' => 'page',
        'title' => $this->randomMachineName(),
        'status' => 1,
      ]);
    $node->save();
    $node->addTranslation('fr', ['title' => 'Non publié', 'status' => 0]);
    $node->save();

    // Create path aliases.
    $alias_storage = $entity_type_manager->getStorage('path_alias');
    $alias_storage->create([
      'path' => '/user/1',
      'alias' => '/secret-identity/peter-parker',
    ])->save();
    $alias_storage->create([
      'path' => '/node/1',
      'langcode' => 'en',
      'alias' => '/press-release/published-report',
    ])->save();
    $alias_storage->create([
      'path' => '/node/1',
      'langcode' => 'fr',
      'alias' => '/press-release/rapport-non-publié',
    ])->save();

    // Visit a restricted user page.
    // Assert that the language switching block is displayed on the
    // access-denied page, but it does not contain the path alias.
    $this->assertLinkMarkup('/user/1', 403, $block->label(), 'peter-parker');

    // Visit the node and its translation. Use internal paths and aliases. The
    // non-ASCII character may be escaped, so remove it from the search string.
    $this->assertLinkMarkup('/node/1', 200, $block->label(), 'rapport-non-publi');
    $this->assertLinkMarkup('/press-release/published-report', 200, $block->label(), 'rapport-non-publi');
    $this->assertLinkMarkup('/fr/node/1', 403, $block->label(), 'rapport-non-publi');
    $this->assertLinkMarkup('/fr/press-release/rapport-non-publié', 403, $block->label(), 'rapport-non-publi');

    // Test as a user with access to other users and unpublished content.
    $privileged_user = $this->drupalCreateUser([
      'access user profiles',
      'bypass node access',
    ]);
    $this->drupalLogin($privileged_user);
    $this->assertLinkMarkup('/user/1', 200, $block->label(), 'peter-parker', TRUE);
    $this->assertLinkMarkup('/node/1', 200, $block->label(), 'rapport-non-publi', TRUE);
    $this->assertLinkMarkup('/press-release/published-report', 200, $block->label(), 'rapport-non-publi', TRUE);
    $this->assertLinkMarkup('/fr/node/1', 200, $block->label(), 'rapport-non-publi', TRUE);
    $this->assertLinkMarkup('/fr/press-release/rapport-non-publié', 200, $block->label(), 'rapport-non-publi', TRUE);

    // Test as an anonymous user.
    $this->drupalLogout();
    $this->assertLinkMarkup('/user/1', 403, $block->label(), 'peter-parker');
    $this->assertLinkMarkup('/node/1', 200, $block->label(), 'rapport-non-publi');
    $this->assertLinkMarkup('/press-release/published-report', 200, $block->label(), 'rapport-non-publi');
    $this->assertLinkMarkup('/fr/node/1', 403, $block->label(), 'rapport-non-publi');
    $this->assertLinkMarkup('/fr/press-release/rapport-non-publié', 403, $block->label(), 'rapport-non-publi');
  }

  /**
   * Asserts that restricted text is or is not present in the page response.
   *
   * @param string $path
   *   The path to test.
   * @param int $status
   *   The HTTP status code, such as 200 or 403.
   * @param string $marker
   *   Text that should always be present.
   * @param string $restricted
   *   Text that should be tested.
   * @param bool $found
   *   (optional) If TRUE, then the restricted text is present. Defaults to
   *   FALSE.
   */
  protected function assertLinkMarkup(string $path, int $status, string $marker, string $restricted, bool $found = FALSE): void {
    $this->drupalGet($path);
    $this->assertSession()->statusCodeEquals($status);
    $this->assertSession()->pageTextContains($marker);
    if ($found) {
      $this->assertSession()->responseContains($restricted);
    }
    else {
      $this->assertSession()->responseNotContains($restricted);
    }

    // Assert that all languages had a link passed to
    // hook_language_switch_links_alter() to allow alternatives to be provided.
    $languages = \Drupal::languageManager()->getNativeLanguages();
    $links_for_alter = \Drupal::state()->get('language_test.language_switch_link_ids');
    $this->assertSame(array_keys($languages), $links_for_alter);
  }

  /**
   * Saves the native name of a language entity in configuration as a label.
   *
   * @param string $langcode
   *   The language code of the language.
   * @param string $label
   *   The native name of the language.
   */
  protected function saveNativeLanguageName($langcode, $label) {
    \Drupal::service('language.config_factory_override')
      ->getOverride($langcode, 'language.entity.' . $langcode)->set('label', $label)->save();
  }

  /**
   * Create a node and set it as the home pages.
   */
  protected function createHomePage() {
    $entity_type_manager = \Drupal::entityTypeManager();

    // Create a node type and make it translatable.
    $entity_type_manager->getStorage('node_type')
      ->create([
        'type' => 'page',
        'name' => 'Page',
      ])
      ->save();

    // Create a published node.
    $node = $entity_type_manager->getStorage('node')
      ->create([
        'type' => 'page',
        'title' => $this->randomMachineName(),
        'status' => 1,
      ]);
    $node->save();

    // Change the front page to /node/1.
    $edit = ['site_frontpage' => '/node/1'];
    $this->drupalGet('admin/config/system/site-information');
    $this->submitForm($edit, 'Save configuration');
  }

}
