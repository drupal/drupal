<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Functional;

use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Ensure the "translate" link is added to shortcut sets.
 *
 * @see \Drupal\Tests\config_translation\Functional\ConfigTranslationListUiTest
 */
#[Group('shortcut')]
#[RunTestsInSeparateProcesses]
class ConfigTranslationListUiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'config_translation',
    'shortcut',
    'toolbar',
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

    $permissions = [
      'administer shortcuts',
      'translate configuration',
    ];

    $this->drupalLogin($this->drupalCreateUser($permissions));

    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)
      ->save();
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests the shortcut listing for the translate operation.
   */
  public function testShortcutListTranslation(): void {
    $shortcut = ShortcutSet::create([
      'id' => $this->randomMachineName(16),
      'label' => $this->randomString(),
    ]);
    $shortcut->save();

    $this->drupalGet('admin/config/user-interface/shortcut');

    $translate_link = 'admin/config/user-interface/shortcut/manage/' . $shortcut->id() . '/translate';
    $this->assertSession()->linkByHrefExists($translate_link);

    $this->drupalGet($translate_link);
    $this->assertSession()->responseContains('<th>Language</th>');
  }

}
