<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Unit;

use Drupal\ckeditor5\LanguageMapper;
use Drupal\ckeditor5\Hook\Ckeditor5Hooks;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;
use Drupal\Tests\ckeditor5\Traits\PrivateMethodUnitTestTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\ckeditor5\Plugin\Editor\CKEditor5.
 *
 * @internal
 */
#[CoversClass(CKEditor5::class)]
#[Group('ckeditor5')]
class CKEditor5Test extends UnitTestCase {

  use PrivateMethodUnitTestTrait;

  /**
   * Simulated CKEditor5::buildConfigurationForm() form structure.
   *
   * @var array
   */
  protected const SIMULATED_FORM_STRUCTURE = [
    'toolbar' => [
      'available' => [],
      'items' => [],
    ],
    'available_items_description' => [],
    'active_items_description' => [],
    'plugin_settings' => [],
    'plugins' => [
      'providerA_plugin1' => [],
      'providerB_plugin2' => [
        'foo' => [],
        'bar' => [],
      ],
    ],
  ];

  /**
   * Tests paths to form names.
   *
   * @legacy-covers \Drupal\ckeditor5\Plugin\Editor\CKEditor5::mapViolationPropertyPathsToFormNames
   */
  #[DataProvider('providerPathsToFormNames')]
  public function testPathsToFormNames(string $property_path, string $expected_form_item_name, bool $expect_exception = FALSE): void {
    $mapMethod = self::getMethod(CKEditor5::class, 'mapViolationPropertyPathsToFormNames');
    if ($expect_exception) {
      $this->expectExceptionMessage('assert($shifted === \'settings\')');
    }

    $form_item_name = $mapMethod->invokeArgs(NULL, [$property_path, static::SIMULATED_FORM_STRUCTURE]);

    if (!$expect_exception) {
      $this->assertSame($expected_form_item_name, $form_item_name);
    }
  }

  /**
   * Data provider for testing mapViolationPropertyPathsToFormNames.
   *
   * @return array[]
   *   An array with the property path and expected form item name.
   */
  public static function providerPathsToFormNames(): array {
    return [
      'validation error targeting toolbar items' => [
        'settings.toolbar.items',
        'settings][toolbar][items',
      ],
      'validation error targeting a specific toolbar item' => [
        'settings.toolbar.items.6',
        'settings][toolbar][items',
      ],
      'validation error targeting a simple plugin form' => [
        'settings.plugins.providerA_plugin1',
        'settings][plugins][providerA_plugin1',
      ],
      'validation error targeting a simple plugin form, with deep config schema detail' => [
        'settings.plugins.providerA_plugin1.foo.bar.baz',
        'settings][plugins][providerA_plugin1',
      ],
      'validation error targeting a complex plugin form' => [
        'settings.plugins.providerB_plugin2',
        'settings][plugins][providerB_plugin2',
      ],
      'validation error targeting a complex plugin form, with deep config schema detail' => [
        'settings.plugins.providerB_plugin2.foo.bar.baz',
        'settings][plugins][providerB_plugin2][foo',
      ],
      'unrealistic example one — should trigger exception' => [
        'bad.bad.worst',
        'I DO NOT EXIST',
        TRUE,
      ],
      'unrealistic example two — should trigger exception' => [
        'one.two.three.four',
        'one][two][three][four',
        TRUE,
      ],
    ];
  }

  /**
   * Test the js_alter hook alters when expected.
   *
   * @legacy-covers \Drupal\ckeditor5\Hook\Ckeditor5Hooks::jsAlter
   */
  public function testJsAlterHook(): void {
    $placeholder_file = 'core/assets/vendor/ckeditor5/translation.js';
    $language_mapper = $this->createMock(LanguageMapper::class);
    $language_mapper->expects($this->any())
      ->method('getMapping')
      ->willReturn('en');
    $hooks = new Ckeditor5Hooks($language_mapper);
    $assets = new AttachedAssets();
    $assets->setLibraries([
      'core/ckeditor5.translations',
      'core/ckeditor5.translations.en',
    ]);
    $language = new Language([
      'id' => 'en',
      'name' => 'English',
      'direction' => 'ltr',
    ]);
    $original_javascript = [
      'keep_this' => [
        'ckeditor5_langcode' => 'en',
      ],
      'remove_this' => [
        'ckeditor5_langcode' => 'sv',
      ],
      'keep_this_too' => [],
    ];
    $expected_javascript = [
      'keep_this' => [
        'ckeditor5_langcode' => 'en',
        'weight' => 5,
      ],
      'keep_this_too' => [],
    ];

    $container = new ContainerBuilder();
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->moduleExists('locale')->willReturn(TRUE);
    $container->set('module_handler', $module_handler->reveal());
    \Drupal::setContainer($container);

    // First check that it filters when the placeholder script is present.
    $javascript = $original_javascript + [
      $placeholder_file => [
        'weight' => 5,
      ],
    ];
    $hooks->jsAlter($javascript, $assets, $language);
    $this->assertEquals($expected_javascript, $javascript);

    // Next check it still filters if the placeholder script has already been
    // loaded and is now excluded from the list, such as an AJAX operation
    // loading a new format which uses another set of plugins.
    $assets = new AttachedAssets();
    $assets->setLibraries([
      'core/ckeditor5.translations.en',
    ]);
    $assets->setAlreadyLoadedLibraries([
      'core/ckeditor5.translations',
    ]);
    $javascript = $original_javascript;
    $hooks->jsAlter($javascript, $assets, $language);
    // There was no placeholder to get the weight from.
    $expected_javascript['keep_this']['weight'] = 0;
    $this->assertEquals($expected_javascript, $javascript);
  }

}
