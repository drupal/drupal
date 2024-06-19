<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Functional;

// cspell:ignore libellé

/**
 * Translate site information to various languages.
 *
 * @group config_translation
 */
class ConfigTranslationUiSiteInformationTest extends ConfigTranslationUiTestBase {

  /**
   * Tests the site information translation interface.
   */
  public function testSiteInformationTranslationUi(): void {
    $this->drupalLogin($this->adminUser);

    $site_name = 'Name of the site for testing configuration translation';
    $site_slogan = 'Site slogan for testing configuration translation';
    $site_name_label = 'Site name';
    $fr_site_name = 'Nom du site pour tester la configuration traduction';
    $fr_site_slogan = 'Slogan du site pour tester la traduction de configuration';
    $fr_site_name_label = 'Libellé du champ "Nom du site"';
    $translation_base_url = 'admin/config/system/site-information/translate';

    // Set site name and slogan for default language.
    $this->setSiteInformation($site_name, $site_slogan);

    $this->drupalGet('admin/config/system/site-information');
    // Check translation tab exist.
    $this->assertSession()->linkByHrefExists($translation_base_url);

    $this->drupalGet($translation_base_url);

    // Check that the 'Edit' link in the source language links back to the
    // original form.
    $this->clickLink('Edit');
    // Also check that saving the form leads back to the translation overview.
    $this->submitForm([], 'Save configuration');
    $this->assertSession()->addressEquals($translation_base_url);

    // Check 'Add' link of French to visit add page.
    $this->assertSession()->linkByHrefExists("$translation_base_url/fr/add");
    $this->clickLink('Add');

    // Make sure original text is present on this page.
    $this->assertSession()->pageTextContains($site_name);
    $this->assertSession()->pageTextContains($site_slogan);

    // Update site name and slogan for French.
    $edit = [
      'translation[config_names][system.site][name]' => $fr_site_name,
      'translation[config_names][system.site][slogan]' => $fr_site_slogan,
    ];

    $this->drupalGet("{$translation_base_url}/fr/add");
    $this->submitForm($edit, 'Save translation');
    $this->assertSession()->pageTextContains('Successfully saved French translation.');

    // Check for edit, delete links (and no 'add' link) for French language.
    $this->assertSession()->linkByHrefNotExists("$translation_base_url/fr/add");
    $this->assertSession()->linkByHrefExists("$translation_base_url/fr/edit");
    $this->assertSession()->linkByHrefExists("$translation_base_url/fr/delete");

    // Check translation saved proper.
    $this->drupalGet("$translation_base_url/fr/edit");
    $this->assertSession()->fieldValueEquals('translation[config_names][system.site][name]', $fr_site_name);
    $this->assertSession()->fieldValueEquals('translation[config_names][system.site][slogan]', $fr_site_slogan);

    // Place branding block with site name and slogan into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    // Check French translation of site name and slogan are in place.
    $this->drupalGet('fr');
    $this->assertSession()->pageTextContains($fr_site_name);
    $this->assertSession()->pageTextContains($fr_site_slogan);

    // Visit French site to ensure base language string present as source.
    $this->drupalGet("fr/$translation_base_url/fr/edit");
    $this->assertSession()->pageTextContains($site_name);
    $this->assertSession()->pageTextContains($site_slogan);

    // Translate 'Site name' label in French.
    $search = [
      'string' => $site_name_label,
      'langcode' => 'fr',
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');

    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = $textarea->getAttribute('name');
    $edit = [
      $lid => $fr_site_name_label,
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');

    // Ensure that the label is in French (and not in English).
    $this->drupalGet("fr/$translation_base_url/fr/edit");
    $this->assertSession()->pageTextContains($fr_site_name_label);
    $this->assertSession()->pageTextNotContains($site_name_label);

    // Ensure that the label is also in French (and not in English)
    // when editing another language with the interface in French.
    $this->drupalGet("fr/$translation_base_url/ta/edit");
    $this->assertSession()->pageTextContains($fr_site_name_label);
    $this->assertSession()->pageTextNotContains($site_name_label);

    // Ensure that the label is not translated when the interface is in English.
    $this->drupalGet("$translation_base_url/fr/edit");
    $this->assertSession()->pageTextContains($site_name_label);
    $this->assertSession()->pageTextNotContains($fr_site_name_label);
  }

  /**
   * Tests the site information translation interface.
   */
  public function testSourceValueDuplicateSave(): void {
    $this->drupalLogin($this->adminUser);

    $site_name = 'Site name for testing configuration translation';
    $site_slogan = 'Site slogan for testing configuration translation';
    $translation_base_url = 'admin/config/system/site-information/translate';
    $this->setSiteInformation($site_name, $site_slogan);

    $this->drupalGet($translation_base_url);

    // Case 1: Update new value for site slogan and site name.
    $edit = [
      'translation[config_names][system.site][name]' => 'FR ' . $site_name,
      'translation[config_names][system.site][slogan]' => 'FR ' . $site_slogan,
    ];
    // First time, no overrides, so just Add link.
    $this->drupalGet("{$translation_base_url}/fr/add");
    $this->submitForm($edit, 'Save translation');

    // Read overridden file from active config.
    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'system.site');

    // Expect both name and slogan in language specific file.
    $expected = [
      'name' => 'FR ' . $site_name,
      'slogan' => 'FR ' . $site_slogan,
    ];
    $this->assertEquals($expected, $override->get());

    // Case 2: Update new value for site slogan and default value for site name.
    $this->drupalGet("$translation_base_url/fr/edit");
    // Assert that the language configuration does not leak outside of the
    // translation form into the actual site name and slogan.
    $this->assertSession()->pageTextNotContains('FR ' . $site_name);
    $this->assertSession()->pageTextNotContains('FR ' . $site_slogan);
    $edit = [
      'translation[config_names][system.site][name]' => $site_name,
      'translation[config_names][system.site][slogan]' => 'FR ' . $site_slogan,
    ];
    $this->submitForm($edit, 'Save translation');
    $this->assertSession()->pageTextContains('Successfully updated French translation.');
    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'system.site');

    // Expect only slogan in language specific file.
    $expected = 'FR ' . $site_slogan;
    $this->assertEquals($expected, $override->get('slogan'));

    // Case 3: Keep default value for site name and slogan.
    $this->drupalGet("$translation_base_url/fr/edit");
    $this->assertSession()->pageTextNotContains('FR ' . $site_slogan);
    $edit = [
      'translation[config_names][system.site][name]' => $site_name,
      'translation[config_names][system.site][slogan]' => $site_slogan,
    ];
    $this->submitForm($edit, 'Save translation');
    $override = \Drupal::languageManager()->getLanguageConfigOverride('fr', 'system.site');

    // Expect no language specific file.
    $this->assertTrue($override->isNew());

    // Check configuration page with translator user. Should have no access.
    $this->drupalLogout();
    $this->drupalLogin($this->translatorUser);
    $this->drupalGet('admin/config/system/site-information');
    $this->assertSession()->statusCodeEquals(403);

    // While translator can access the translation page, the edit link is not
    // present due to lack of permissions.
    $this->drupalGet($translation_base_url);
    $this->assertSession()->linkNotExists('Edit');

    // Check 'Add' link for French.
    $this->assertSession()->linkByHrefExists("$translation_base_url/fr/add");
  }

}
