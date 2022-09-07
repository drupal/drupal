<?php

namespace Drupal\Tests\ckeditor\Unit\Plugin\CKEditorPlugin;

use Drupal\ckeditor\Plugin\CKEditorPlugin\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor\Plugin\CKEditorPlugin\Language
 *
 * @group ckeditor
 * @group legacy
 */
class LanguageTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\ckeditor\Plugin\CKEditorPlugin\Language
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->plugin = new Language([], $this->randomMachineName(), []);
  }

  /**
   * Provides a list of configs to test.
   */
  public function providerGetConfig() {
    return [
      ['un', LanguageManager::getUnitedNationsLanguageList()],
      ['all', LanguageManager::getStandardLanguageList()],
    ];
  }

  /**
   * @covers ::getConfig
   *
   * @dataProvider providerGetConfig
   */
  public function testGetConfig($language_list, $expected_languages) {
    $editor = $this->getMockBuilder('Drupal\editor\Entity\Editor')
      ->disableOriginalConstructor()
      ->getMock();
    $editor->expects($this->once())
      ->method('getSettings')
      ->willReturn(['plugins' => ['language' => ['language_list' => $language_list]]]);

    $config = $this->plugin->getConfig($editor);

    $this->assertIsArray($config);
    $this->assertContains('ar:Arabic:rtl', $config['language_list']);
    $this->assertContains('zh-hans:Chinese, Simplified', $config['language_list']);
    $this->assertContains('en:English', $config['language_list']);
    $this->assertContains('fr:French', $config['language_list']);
    $this->assertContains('ru:Russian', $config['language_list']);
    $this->assertContains('ar:Arabic:rtl', $config['language_list']);
    $this->assertSameSize($expected_languages, $config['language_list']);
  }

}
