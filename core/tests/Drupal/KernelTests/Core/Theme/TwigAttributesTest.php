<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme;

use Drupal\Core\Template\Attribute;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Twig with Attribute objects.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class TwigAttributesTest extends KernelTestBase {

  /**
   * Tests that Attributes are rendered at the correct time within a macro.
   */
  public function testYield(): void {
    /** @var \Drupal\Core\Template\TwigEnvironment $environment */
    $environment = \Drupal::service('twig');

    $template = '{{ var }}{% set var = var + 1 %}{{ var }}';
    $var = 0;
    $this->assertEquals('01', $environment->renderInline($template, ['var' => $var]));
    $this->assertEquals('01', $environment->renderInline($template, ['var' => &$var]));

    $template = '{{ attributes.addClass("test2") }}{% set attributes = attributes.removeClass("test2") %}{{ attributes }}';
    $attributes = new Attribute(['class' => 'test1']);
    $this->assertEquals(' class="test1 test2" class="test1"', $environment->renderInline($template, ['attributes' => $attributes]));

    $template = '{{ _self.macro_test(var) }}{% macro macro_test(var) %}{{ var }}{% set var = var + 1 %}{{ var }}{% endmacro %}';
    $var = 0;
    $this->assertEquals('01', $environment->renderInline($template, ['var' => $var]));
    $this->assertEquals('01', $environment->renderInline($template, ['var' => &$var]));

    $template = '{{ _self.macro_test(attributes) }}{% macro macro_test(attributes) %}{{ attributes.addClass("test2") }}{% set attributes = attributes.removeClass("test2") %}{{ attributes }}{% endmacro %}';
    $attributes = new Attribute(['class' => 'test1']);
    $this->assertEquals(' class="test1 test2" class="test1"', $environment->renderInline($template, ['attributes' => $attributes]));
  }

}
