<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Kernel\ConfigAction;

use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Recipe\InvalidConfigException;
use Drupal\Core\Recipe\RecipeRunner;
use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\KernelTests\KernelTestBase;

/**
 * @covers \Drupal\ckeditor5\Plugin\ConfigAction\AddItemToToolbar
 * @group ckeditor5
 * @group Recipe
 */
class AddItemToToolbarConfigActionTest extends KernelTestBase {

  use RecipeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'editor',
    'filter',
    'filter_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    // This test must be allowed to save invalid config, we can confirm that
    // any invalid stuff is validated by the config actions system.
    'editor.editor.filter_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('filter_test');

    $editor = Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'filter_test',
      'image_upload' => ['status' => FALSE],
    ]);
    $editor->save();

    /** @var array{toolbar: array{items: array<int, string>}} $settings */
    $settings = Editor::load('filter_test')?->getSettings();
    $this->assertSame(['heading', 'bold', 'italic'], $settings['toolbar']['items']);
  }

  /**
   * @param string|array<string, mixed> $action
   *   The value to pass to the config action.
   * @param string[] $expected_toolbar_items
   *   The items which should be in the editor toolbar, in the expected order.
   *
   * @testWith ["sourceEditing", ["heading", "bold", "italic", "sourceEditing"]]
   *   [{"item_name": "sourceEditing"}, ["heading", "bold", "italic", "sourceEditing"]]
   *   [{"item_name": "sourceEditing", "position": 1}, ["heading", "sourceEditing", "bold", "italic"]]
   *   [{"item_name": "sourceEditing", "position": 1, "replace": true}, ["heading", "sourceEditing", "italic"]]
   */
  public function testAddItemToToolbar(string|array $action, array $expected_toolbar_items): void {
    $recipe = $this->createRecipe([
      'name' => 'CKEditor 5 toolbar item test',
      'config' => [
        'actions' => [
          'editor.editor.filter_test' => [
            'addItemToToolbar' => $action,
          ],
        ],
      ],
    ]);
    RecipeRunner::processRecipe($recipe);

    /** @var array{toolbar: array{items: string[]}, plugins: array<string, array<mixed>>} $settings */
    $settings = Editor::load('filter_test')?->getSettings();
    $this->assertSame($expected_toolbar_items, $settings['toolbar']['items']);
    // The plugin's default settings should have been added.
    $this->assertSame([], $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']);
  }

  public function testAddNonExistentItem(): void {
    $recipe = $this->createRecipe([
      'name' => 'Add an invalid toolbar item',
      'config' => [
        'actions' => [
          'editor.editor.filter_test' => [
            'addItemToToolbar' => 'bogus_item',
          ],
        ],
      ],
    ]);

    $this->expectException(InvalidConfigException::class);
    $this->expectExceptionMessage("There were validation errors in editor.editor.filter_test:\n- settings.toolbar.items.3: The provided toolbar item <em class=\"placeholder\">bogus_item</em> is not valid.");
    RecipeRunner::processRecipe($recipe);
  }

  public function testActionRequiresCKEditor5(): void {
    $this->enableModules(['editor_test']);
    Editor::load('filter_test')?->setEditor('unicorn')->setSettings([])->save();

    $recipe = <<<YAML
name: Not a CKEditor
config:
  actions:
    editor.editor.filter_test:
      addItemToToolbar: strikethrough
YAML;

    $this->expectException(ConfigActionException::class);
    $this->expectExceptionMessage('The editor:addItemToToolbar config action only works with editors that use CKEditor 5.');
    RecipeRunner::processRecipe($this->createRecipe($recipe));
  }

}
