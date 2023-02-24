<?php

namespace Drupal\FunctionalTests\Core\Locale;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Country Manager functionality.
 *
 * @group CountryManager
 * @covers Drupal\Core\Locale\CountryManager
 */
class CountryManagerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * Admin user account.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the translation of country names.
   */
  public function testCountryManagerTranslate() {
    $countryManager = $this->container->get('country_manager');

    // Add French language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('/admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // French country list.
    $countryList = $countryManager->getStandardList('fr');
    $this->assertEquals('ES', array_search(
    // cSpell:disable-next-line
      'Espagne',
      $countryList));

    // English country list.
    $countryList = $countryManager->getStandardList('en');
    $this->assertEquals('ES', array_search(
      'Spain',
      $countryList));
  }

  /**
   * Tests sorting of countries.
   */
  public function testCountryManagerSort() {
    $countryManager = $this->container->get('country_manager');
    $countryList = $countryManager->getList(LanguageInterface::TYPE_INTERFACE);
    $countryIds = array_keys($countryList);
    // cSpell:disable-next-line
    // Fails if Zimbabwe is in front of Åland Islands, wrong alphabetical order.
    $this->assertTrue(array_search('AX', $countryIds)
      < array_search('ZW', $countryIds));
  }

}
