<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

// cspell:ignore corge grault quux
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\Icon\Exception\IconDefinitionInvalidDataException;
use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Icon\IconDefinition
 *
 * @group icon
 */
class IconDefinitionTest extends UnitTestCase {

  /**
   * Data provider for ::testCreateIcon().
   *
   * @return \Generator
   *   The test cases.
   */
  public static function providerCreateIcon(): iterable {
    yield 'minimal icon' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
      ],
    ];

    yield 'icon with source' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
        'source' => 'foo/bar',
      ],
    ];

    yield 'icon with empty source' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
        'source' => '',
      ],
    ];

    yield 'icon with empty data' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
        'source' => '',
        'group' => '',
        'data' => [
          'content' => '',
          'library' => '',
        ],
      ],
    ];

    yield 'icon with null data' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
        'source' => NULL,
        'group' => NULL,
        'data' => [
          'content' => NULL,
          'library' => NULL,
        ],
      ],
    ];

    yield 'icon with data' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
        'source' => 'foo/bar',
        'group' => 'quux',
        'data' => [
          'content' => 'corge',
          'label' => new TranslatableMarkup('Qux'),
          'library' => 'foo/bar',
          'foo' => 'bar',
        ],
      ],
    ];
  }

  /**
   * Test the IconDefinition::createIcon method.
   *
   * @param array $data
   *   The icon data.
   *
   * @dataProvider providerCreateIcon
   */
  public function testCreateIcon(array $data): void {
    $icon_data = $data['data'] ?? NULL;

    if ($icon_data) {
      $actual = IconDefinition::create(
        $data['pack_id'],
        $data['icon_id'],
        $data['template'],
        $data['source'] ?? NULL,
        $data['group'] ?? NULL,
        $icon_data,
      );
    }
    else {
      $actual = IconDefinition::create(
        $data['pack_id'],
        $data['icon_id'],
        $data['template'],
        $data['source'] ?? NULL,
        $data['group'] ?? NULL,
      );
    }

    $icon_full_id = IconDefinition::createIconId($data['pack_id'], $data['icon_id']);

    $this->assertEquals($icon_full_id, $actual->getId());

    $this->assertEquals(IconDefinition::humanize($data['icon_id']), $actual->getLabel());

    $this->assertEquals($data['icon_id'], $actual->getIconId());
    $this->assertEquals($data['pack_id'], $actual->getPackId());
    $this->assertEquals($data['template'], $actual->getTemplate());

    if (isset($data['source'])) {
      $this->assertEquals($data['source'], $actual->getSource());
    }
    if (isset($data['group'])) {
      $this->assertEquals($data['group'], $actual->getGroup());
    }

    if ($icon_data) {
      if (isset($icon_data['library'])) {
        $this->assertEquals($icon_data['library'], $actual->getLibrary());
        unset($icon_data['library']);
      }
      if (isset($icon_data['label'])) {
        $this->assertSame($icon_data['label'], $actual->getPackLabel());
        unset($icon_data['label']);
      }
      foreach ($icon_data as $key => $value) {
        $this->assertEquals($icon_data[$key], $actual->getData($key));
      }
    }
  }

  /**
   * Test the IconDefinition::create method with errors.
   */
  public function testCreateIconError(): void {
    $this->expectException(IconDefinitionInvalidDataException::class);
    $this->expectExceptionMessage('Empty pack_id provided! Empty icon_id provided! Empty template provided!');

    IconDefinition::create('', '', '');
  }

  /**
   * Data provider for ::testCreateIcon().
   *
   * @return array
   *   The test cases with icon_id and expected label.
   */
  public static function providerCreateIconHumanize(): array {
    return [
      'simple' => [
        'foo',
        'Foo',
      ],
      'with space' => [
        '  foo  ',
        'Foo',
      ],
      'with parts' => [
        'foo_bar',
        'Foo Bar',
      ],
      'with capital letters' => [
        'foo BAR bάz',
        'Foo Bar Bάz',
      ],
      'long with special characters' => [
        'foo --  Bar (1) -24',
        'Foo Bar124',
      ],
      'special characters' => [
        'j([{Fh- x1|_e$0__--Zr|7]ç$U_nE -B',
        'J Fh X1 E0 Zr7 Ç Un Eb',
      ],
    ];
  }

  /**
   * Test the IconDefinition::humanize method.
   *
   * @param string $icon_id
   *   The icon id.
   * @param string $expected
   *   The expected label.
   *
   * @dataProvider providerCreateIconHumanize
   */
  public function testCreateIconHumanize(string $icon_id, string $expected): void {
    $icon = IconDefinition::create('bar', $icon_id, 'baz');
    $this->assertEquals($expected, $icon->getLabel());
  }

  /**
   * Test the IconDefinition::getRenderable method.
   */
  public function testGetRenderable(): void {
    $expected = [
      '#type' => 'icon',
      '#pack_id' => 'foo',
      '#icon_id' => 'bar',
      '#settings' => [
        'baz' => 'corge',
      ],
    ];

    $actual = IconDefinition::getRenderable('foo:bar', ['baz' => 'corge']);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for ::testGetPreview().
   *
   * @return \Generator
   *   Provide test data as icon data and expected result.
   */
  public static function providerGetPreview(): iterable {
    yield 'minimal icon' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
      ],
      [],
      [
        '#icon_label' => 'Bar',
        '#icon_id' => 'bar',
        '#pack_id' => 'foo',
        '#extractor' => NULL,
        '#source' => NULL,
        '#library' => NULL,
        '#settings' => [],
      ],
    ];

    yield 'minimal icon with settings' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
      ],
      [
        'baz' => 'corge',
        0 => 1,
        'grault', // phpcs:disable
      ],
      [
        '#icon_label' => 'Bar',
        '#icon_id' => 'bar',
        '#pack_id' => 'foo',
        '#extractor' => NULL,
        '#source' => NULL,
        '#library' => NULL,
        '#settings' => [
          'baz' => 'corge',
          0 => 1,
          1 => 'grault',
        ],
      ],
    ];

    yield 'icon with data and settings' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
        'data' => [
          'extractor' => 'qux',
        ],
      ],
      ['baz' => 'corge'],
      [
        '#icon_label' => 'Bar',
        '#icon_id' => 'bar',
        '#pack_id' => 'foo',
        '#extractor' => 'qux',
        '#source' => NULL,
        '#library' => NULL,
        '#settings' => ['baz' => 'corge'],
      ],
    ];

    yield 'icon with data' => [
      [
        'pack_id' => 'foo',
        'icon_id' => 'bar',
        'template' => 'baz',
        'source' => 'quux',
        'data' => [
          'extractor' => 'qux',
          'library' => 'corge',
        ],
      ],
      ['baz' => 'corge'],
      [
        '#icon_label' => 'Bar',
        '#icon_id' => 'bar',
        '#pack_id' => 'foo',
        '#extractor' => 'qux',
        '#source' => 'quux',
        '#library' => 'corge',
        '#settings' => ['baz' => 'corge'],
      ],
    ];
  }

}
