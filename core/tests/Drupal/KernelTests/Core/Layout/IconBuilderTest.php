<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Layout;

use Drupal\Core\Layout\Icon\SvgIconBuilder;
use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Layout\Icon\SvgIconBuilder
 * @group Layout
 */
class IconBuilderTest extends KernelTestBase {

  /**
   * @covers ::build
   * @covers ::buildRenderArray
   * @covers ::calculateSvgValues
   * @covers ::getLength
   * @covers ::getOffset
   *
   * @dataProvider providerTestBuild
   */
  public function testBuild(SvgIconBuilder $icon_builder, $icon_map, $expected): void {
    $renderer = $this->container->get('renderer');

    $build = $icon_builder->build($icon_map);

    $output = (string) $renderer->executeInRenderContext(new RenderContext(), function () use ($build, $renderer) {
      return $renderer->render($build);
    });
    $this->assertSame($expected, $output);
  }

  public static function providerTestBuild() {
    $data = [];
    $data['empty'][] = (new SvgIconBuilder());
    $data['empty'][] = [];
    $data['empty'][] = <<<'EOD'
<svg width="125" height="150" class="layout-icon"></svg>

EOD;

    $data['two_column'][] = (new SvgIconBuilder())
      ->setId('two_column')
      ->setLabel('Two Column')
      ->setWidth(250)
      ->setHeight(300)
      ->setStrokeWidth(2);
    $data['two_column'][] = [['left', 'right']];
    $data['two_column'][] = <<<'EOD'
<svg width="250" height="300" class="layout-icon layout-icon--two-column"><title>Two Column</title>
<g><title>left</title>
<rect x="1" y="1" width="121" height="298" stroke-width="2" class="layout-icon__region layout-icon__region--left" />
</g>
<g><title>right</title>
<rect x="128" y="1" width="121" height="298" stroke-width="2" class="layout-icon__region layout-icon__region--right" />
</g>
</svg>

EOD;

    $data['two_column_no_stroke'][] = (new SvgIconBuilder())
      ->setWidth(250)
      ->setHeight(300)
      ->setStrokeWidth(NULL);
    $data['two_column_no_stroke'][] = [['left', 'right']];
    $data['two_column_no_stroke'][] = <<<'EOD'
<svg width="250" height="300" class="layout-icon"><g><title>left</title>
<rect x="0" y="0" width="123" height="300" class="layout-icon__region layout-icon__region--left" />
</g>
<g><title>right</title>
<rect x="127" y="0" width="123" height="300" class="layout-icon__region layout-icon__region--right" />
</g>
</svg>

EOD;

    $data['two_column_border_collapse'][] = (new SvgIconBuilder())
      ->setWidth(250)
      ->setHeight(300)
      ->setStrokeWidth(2)
      ->setPadding(-2);
    $data['two_column_border_collapse'][] = [['left', 'right']];
    $data['two_column_border_collapse'][] = <<<'EOD'
<svg width="250" height="300" class="layout-icon"><g><title>left</title>
<rect x="1" y="1" width="124" height="298" stroke-width="2" class="layout-icon__region layout-icon__region--left" />
</g>
<g><title>right</title>
<rect x="125" y="1" width="124" height="298" stroke-width="2" class="layout-icon__region layout-icon__region--right" />
</g>
</svg>

EOD;

    $data['stacked'][] = (new SvgIconBuilder())
      ->setStrokeWidth(2);
    $data['stacked'][] = [
      ['sidebar', 'top', 'top'],
      ['sidebar', 'left', 'right'],
      ['sidebar', 'middle', 'middle'],
      ['footer_left', 'footer_right'],
      ['footer_full'],
    ];
    $data['stacked'][] = <<<'EOD'
<svg width="125" height="150" class="layout-icon"><g><title>sidebar</title>
<rect x="1" y="1" width="37" height="86.4" stroke-width="2" class="layout-icon__region layout-icon__region--sidebar" />
</g>
<g><title>top</title>
<rect x="44" y="1" width="80" height="24.8" stroke-width="2" class="layout-icon__region layout-icon__region--top" />
</g>
<g><title>left</title>
<rect x="44" y="31.8" width="37" height="24.8" stroke-width="2" class="layout-icon__region layout-icon__region--left" />
</g>
<g><title>right</title>
<rect x="87" y="31.8" width="37" height="24.8" stroke-width="2" class="layout-icon__region layout-icon__region--right" />
</g>
<g><title>middle</title>
<rect x="44" y="62.6" width="80" height="24.8" stroke-width="2" class="layout-icon__region layout-icon__region--middle" />
</g>
<g><title>footer_left</title>
<rect x="1" y="93.4" width="58.5" height="24.8" stroke-width="2" class="layout-icon__region layout-icon__region--footer-left" />
</g>
<g><title>footer_right</title>
<rect x="65.5" y="93.4" width="58.5" height="24.8" stroke-width="2" class="layout-icon__region layout-icon__region--footer-right" />
</g>
<g><title>footer_full</title>
<rect x="1" y="124.2" width="123" height="24.8" stroke-width="2" class="layout-icon__region layout-icon__region--footer-full" />
</g>
</svg>

EOD;

    return $data;
  }

}
