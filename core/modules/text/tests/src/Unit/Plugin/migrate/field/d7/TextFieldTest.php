<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Unit\Plugin\migrate\field\d7;

use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;
use Drupal\text\Plugin\migrate\field\d7\TextField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\text\Plugin\migrate\field\d7\TextField.
 */
#[CoversClass(TextField::class)]
#[Group('text')]
class TextFieldTest extends UnitTestCase {

  /**
   * The migration field plugin.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new TextField([], 'text', []);
  }

  /**
   * Data provider for testGetFieldFormatterType().
   */
  public static function getFieldFormatterTypeProvider() {
    return [
      ['text', 'text_plain', 'string'],
      ['text_long', 'text_default', 'basic_string'],
      ['text_long', 'text_plain', 'basic_string'],
    ];
  }

  /**
   * Tests get field formatter type.
   *
   * @legacy-covers ::getFieldFormatterType
   * @legacy-covers ::getFieldType
   */
  #[DataProvider('getFieldFormatterTypeProvider')]
  public function testGetFieldFormatterType($type, $formatter_type, $expected): void {
    $row = new Row();
    $row->setSourceProperty('type', $type);
    $row->setSourceProperty('formatter/type', $formatter_type);
    $row->setSourceProperty('instances', [
      [
        'data' => serialize([
          'settings' => [
            'text_processing' => '0',
          ],
        ]),
      ],
    ]);
    $this->assertEquals($expected, $this->plugin->getFieldFormatterType($row));
  }

}
