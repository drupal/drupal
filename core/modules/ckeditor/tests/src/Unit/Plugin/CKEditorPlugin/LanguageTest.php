<?php

namespace Drupal\Tests\ckeditor\Unit\Plugin\CKEditorPlugin;

use Drupal\ckeditor\Plugin\CKEditorPlugin\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor\Plugin\CKEditorPlugin\Language
 *
 * @group ckeditor
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
  public function setUp() {
    $this->plugin = new Language([], $this->randomMachineName(), []);
  }

  /**
   * Provides a list of configs to test.
   */
  public function providerGetConfig() {
    return [
      ['un', count(LanguageManager::getUnitedNationsLanguageList())],
      ['all', count(LanguageManager::getStandardLanguageList())],
    ];
  }

  /**
   * @covers ::getConfig
   *
   * @dataProvider providerGetConfig
   */
  public function testGetConfig($language_list, $expected_number) {
    $editor = $this->getMockBuilder('Drupal\editor\Entity\Editor')
      ->disableOriginalConstructor()
      ->getMock();
    $editor->expects($this->once())
      ->method('getSettings')
      ->willReturn(['plugins' => ['language' => ['language_list' => $language_list]]]);

    $config = $this->plugin->getConfig($editor);

    $this->assertInternalType('array', $config);
    $this->assertTrue(in_array('ar:Arabic:rtl', $config['language_list']));
    $this->assertTrue(in_array('zh-hans:Chinese, Simplified', $config['language_list']));
    $this->assertTrue(in_array('en:English', $config['language_list']));
    $this->assertTrue(in_array('fr:French', $config['language_list']));
    $this->assertTrue(in_array('ru:Russian', $config['language_list']));
    $this->assertTrue(in_array('ar:Arabic:rtl', $config['language_list']));
    $this->assertEquals($expected_number, count($config['language_list']));
  }

}
