<?php

/**
 * @file
 * Contains \Drupal\KernelTests\Core\Theme\TwigMarkupInterfaceTest.
 */

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests Twig with MarkupInterface objects.
 *
 * @group Theme
 */
class TwigMarkupInterfaceTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
  ];

  /**
   * @dataProvider providerTestMarkupInterfaceEmpty
   */
  public function testMarkupInterfaceEmpty($expected, $variable) {
    $this->assertEquals($expected, $this->renderObjectWithTwig($variable));
  }

  /**
   * Provide test examples.
   */
  public function providerTestMarkupInterfaceEmpty() {
    return [
      // @codingStandardsIgnoreStart
      // The first argument to \Drupal\Core\StringTranslation\TranslatableMarkup
      // is not supposed to be an empty string.
      'empty TranslatableMarkup' => ['', new TranslatableMarkup('')],
      // @codingStandardsIgnoreEnd
      'non-empty TranslatableMarkup' => ['<span>test</span>', new TranslatableMarkup('test')],
      'empty FormattableMarkup' => ['', new FormattableMarkup('', ['@foo' => 'bar'])],
      'non-empty FormattableMarkup' => ['<span>bar</span>', new FormattableMarkup('@foo', ['@foo' => 'bar'])],
      'non-empty Markup' => ['<span>test</span>', Markup::create('test')],
      'empty GeneratedLink' => ['', new GeneratedLink()],
      'non-empty GeneratedLink' => ['<span><a hef="http://www.example.com">test</a></span>', (new GeneratedLink())->setGeneratedLink('<a hef="http://www.example.com">test</a>')],
      // Test objects that do not implement \Countable.
      'empty SafeMarkupTestMarkup' => ['', SafeMarkupTestMarkup::create('')],
      'non-empty SafeMarkupTestMarkup' => ['<span>test</span>', SafeMarkupTestMarkup::create('test')],
    ];
  }

  /**
   * Tests behavior if a string is translated to become an empty string.
   */
  public function testEmptyTranslation() {
    $settings = Settings::getAll();
    $settings['locale_custom_strings_en'] = ['' => ['test' => '']];
    // Recreate the settings static.
    new Settings($settings);

    $variable = new TranslatableMarkup('test');
    $this->assertEquals('', $this->renderObjectWithTwig($variable));

    $variable = new TranslatableMarkup('test', [], ['langcode' => 'de']);
    $this->assertEquals('<span>test</span>', $this->renderObjectWithTwig($variable));
  }

  /**
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  protected function renderObjectWithTwig($variable) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $context = new RenderContext();
    return $renderer->executeInRenderContext($context, function () use ($renderer, $variable) {
      $elements = [
        '#type' => 'inline_template',
        '#template' => '{%- if variable is not empty -%}<span>{{ variable }}</span>{%- endif -%}',
        '#context' => ['variable' => $variable],
      ];
      return $renderer->render($elements);
    });
  }

}

/**
 * Implements MarkupInterface without implementing \Countable.
 */
class SafeMarkupTestMarkup implements MarkupInterface {
  use MarkupTrait;

  /**
   * Overrides MarkupTrait::create() to allow creation with empty strings.
   */
  public static function create($string) {
    $object = new static();
    $object->string = $string;
    return $object;
  }

}
