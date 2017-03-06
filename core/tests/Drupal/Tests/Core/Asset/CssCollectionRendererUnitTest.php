<?php

namespace Drupal\Tests\Core\Asset;

use Drupal\Core\Asset\CssCollectionRenderer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CSS asset collection renderer.
 *
 * @group Asset
 */
class CssCollectionRendererUnitTest extends UnitTestCase {

  /**
   * A CSS asset renderer.
   *
   * @var \Drupal\Core\Asset\CssRenderer object.
   */
  protected $renderer;

  /**
   * A valid file CSS asset group.
   *
   * @var array
   */
  protected $fileCssGroup;

  /**
   * The state mock class.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $state;

  protected function setUp() {
    parent::setUp();

    $this->state = $this->getMock('Drupal\Core\State\StateInterface');

    $this->renderer = new CssCollectionRenderer($this->state);
    $this->fileCssGroup = [
      'group' => -100,
      'type' => 'file',
      'media' => 'all',
      'preprocess' => TRUE,
      'browsers' => ['IE' => TRUE, '!IE' => TRUE],
      'items' => [
        0 => [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.012,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => 'tests/Drupal/Tests/Core/Asset/foo.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'foo.css',
        ],
        1 => [
          'group' => -100,
          'type' => 'file',
          'weight' => 0.013,
          'media' => 'all',
          'preprocess' => TRUE,
          'data' => 'tests/Drupal/Tests/Core/Asset/bar.css',
          'browsers' => ['IE' => TRUE, '!IE' => TRUE],
          'basename' => 'bar.css',
        ],
      ],
    ];
  }

  /**
   * Provides data for the CSS asset rendering test.
   *
   * @see testRender
   */
  public function providerTestRender() {
    $create_link_element = function($href, $media = 'all', $browsers = []) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'link',
        '#attributes' => [
          'rel' => 'stylesheet',
          'href' => $href,
          'media' => $media,
        ],
        '#browsers' => $browsers,
      ];
    };
    $create_style_element = function($value, $media, $browsers = []) {
      $style_element = [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => $value,
        '#attributes' => [
          'media' => $media
        ],
        '#browsers' => $browsers,
      ];
      return $style_element;
    };

    $create_file_css_asset = function($data, $media = 'all', $preprocess = TRUE) {
      return ['group' => 0, 'type' => 'file', 'media' => $media, 'preprocess' => $preprocess, 'data' => $data, 'browsers' => []];
    };

    return [
      // Single external CSS asset.
      0 => [
        // CSS assets.
        [
          0 => ['group' => 0, 'type' => 'external', 'media' => 'all', 'preprocess' => TRUE, 'data' => 'http://example.com/popular.js', 'browsers' => []],
        ],
        // Render elements.
        [
          0 => $create_link_element('http://example.com/popular.js', 'all'),
        ],
      ],
      // Single file CSS asset.
      2 => [
        [
          0 => ['group' => 0, 'type' => 'file', 'media' => 'all', 'preprocess' => TRUE, 'data' => 'public://css/file-all', 'browsers' => []],
        ],
        [
          0 => $create_link_element(file_url_transform_relative(file_create_url('public://css/file-all')) . '?0', 'all'),
        ],
      ],
      // 31 file CSS assets: expect 31 link elements.
      3 => [
        [
          0 => $create_file_css_asset('public://css/1.css'),
          1 => $create_file_css_asset('public://css/2.css'),
          2 => $create_file_css_asset('public://css/3.css'),
          3 => $create_file_css_asset('public://css/4.css'),
          4 => $create_file_css_asset('public://css/5.css'),
          5 => $create_file_css_asset('public://css/6.css'),
          6 => $create_file_css_asset('public://css/7.css'),
          7 => $create_file_css_asset('public://css/8.css'),
          8 => $create_file_css_asset('public://css/9.css'),
          9 => $create_file_css_asset('public://css/10.css'),
          10 => $create_file_css_asset('public://css/11.css'),
          11 => $create_file_css_asset('public://css/12.css'),
          12 => $create_file_css_asset('public://css/13.css'),
          13 => $create_file_css_asset('public://css/14.css'),
          14 => $create_file_css_asset('public://css/15.css'),
          15 => $create_file_css_asset('public://css/16.css'),
          16 => $create_file_css_asset('public://css/17.css'),
          17 => $create_file_css_asset('public://css/18.css'),
          18 => $create_file_css_asset('public://css/19.css'),
          19 => $create_file_css_asset('public://css/20.css'),
          20 => $create_file_css_asset('public://css/21.css'),
          21 => $create_file_css_asset('public://css/22.css'),
          22 => $create_file_css_asset('public://css/23.css'),
          23 => $create_file_css_asset('public://css/24.css'),
          24 => $create_file_css_asset('public://css/25.css'),
          25 => $create_file_css_asset('public://css/26.css'),
          26 => $create_file_css_asset('public://css/27.css'),
          27 => $create_file_css_asset('public://css/28.css'),
          28 => $create_file_css_asset('public://css/29.css'),
          29 => $create_file_css_asset('public://css/30.css'),
          30 => $create_file_css_asset('public://css/31.css'),
        ],
        [
          0 => $create_link_element(file_url_transform_relative(file_create_url('public://css/1.css')) . '?0'),
          1 => $create_link_element(file_url_transform_relative(file_create_url('public://css/2.css')) . '?0'),
          2 => $create_link_element(file_url_transform_relative(file_create_url('public://css/3.css')) . '?0'),
          3 => $create_link_element(file_url_transform_relative(file_create_url('public://css/4.css')) . '?0'),
          4 => $create_link_element(file_url_transform_relative(file_create_url('public://css/5.css')) . '?0'),
          5 => $create_link_element(file_url_transform_relative(file_create_url('public://css/6.css')) . '?0'),
          6 => $create_link_element(file_url_transform_relative(file_create_url('public://css/7.css')) . '?0'),
          7 => $create_link_element(file_url_transform_relative(file_create_url('public://css/8.css')) . '?0'),
          8 => $create_link_element(file_url_transform_relative(file_create_url('public://css/9.css')) . '?0'),
          9 => $create_link_element(file_url_transform_relative(file_create_url('public://css/10.css')) . '?0'),
          10 => $create_link_element(file_url_transform_relative(file_create_url('public://css/11.css')) . '?0'),
          11 => $create_link_element(file_url_transform_relative(file_create_url('public://css/12.css')) . '?0'),
          12 => $create_link_element(file_url_transform_relative(file_create_url('public://css/13.css')) . '?0'),
          13 => $create_link_element(file_url_transform_relative(file_create_url('public://css/14.css')) . '?0'),
          14 => $create_link_element(file_url_transform_relative(file_create_url('public://css/15.css')) . '?0'),
          15 => $create_link_element(file_url_transform_relative(file_create_url('public://css/16.css')) . '?0'),
          16 => $create_link_element(file_url_transform_relative(file_create_url('public://css/17.css')) . '?0'),
          17 => $create_link_element(file_url_transform_relative(file_create_url('public://css/18.css')) . '?0'),
          18 => $create_link_element(file_url_transform_relative(file_create_url('public://css/19.css')) . '?0'),
          19 => $create_link_element(file_url_transform_relative(file_create_url('public://css/20.css')) . '?0'),
          20 => $create_link_element(file_url_transform_relative(file_create_url('public://css/21.css')) . '?0'),
          21 => $create_link_element(file_url_transform_relative(file_create_url('public://css/22.css')) . '?0'),
          22 => $create_link_element(file_url_transform_relative(file_create_url('public://css/23.css')) . '?0'),
          23 => $create_link_element(file_url_transform_relative(file_create_url('public://css/24.css')) . '?0'),
          24 => $create_link_element(file_url_transform_relative(file_create_url('public://css/25.css')) . '?0'),
          25 => $create_link_element(file_url_transform_relative(file_create_url('public://css/26.css')) . '?0'),
          26 => $create_link_element(file_url_transform_relative(file_create_url('public://css/27.css')) . '?0'),
          27 => $create_link_element(file_url_transform_relative(file_create_url('public://css/28.css')) . '?0'),
          28 => $create_link_element(file_url_transform_relative(file_create_url('public://css/29.css')) . '?0'),
          29 => $create_link_element(file_url_transform_relative(file_create_url('public://css/30.css')) . '?0'),
          30 => $create_link_element(file_url_transform_relative(file_create_url('public://css/31.css')) . '?0'),
        ],
      ],
      // 32 file CSS assets with the same properties: expect 2 style elements.
      4 => [
        [
          0 => $create_file_css_asset('public://css/1.css'),
          1 => $create_file_css_asset('public://css/2.css'),
          2 => $create_file_css_asset('public://css/3.css'),
          3 => $create_file_css_asset('public://css/4.css'),
          4 => $create_file_css_asset('public://css/5.css'),
          5 => $create_file_css_asset('public://css/6.css'),
          6 => $create_file_css_asset('public://css/7.css'),
          7 => $create_file_css_asset('public://css/8.css'),
          8 => $create_file_css_asset('public://css/9.css'),
          9 => $create_file_css_asset('public://css/10.css'),
          10 => $create_file_css_asset('public://css/11.css'),
          11 => $create_file_css_asset('public://css/12.css'),
          12 => $create_file_css_asset('public://css/13.css'),
          13 => $create_file_css_asset('public://css/14.css'),
          14 => $create_file_css_asset('public://css/15.css'),
          15 => $create_file_css_asset('public://css/16.css'),
          16 => $create_file_css_asset('public://css/17.css'),
          17 => $create_file_css_asset('public://css/18.css'),
          18 => $create_file_css_asset('public://css/19.css'),
          19 => $create_file_css_asset('public://css/20.css'),
          20 => $create_file_css_asset('public://css/21.css'),
          21 => $create_file_css_asset('public://css/22.css'),
          22 => $create_file_css_asset('public://css/23.css'),
          23 => $create_file_css_asset('public://css/24.css'),
          24 => $create_file_css_asset('public://css/25.css'),
          25 => $create_file_css_asset('public://css/26.css'),
          26 => $create_file_css_asset('public://css/27.css'),
          27 => $create_file_css_asset('public://css/28.css'),
          28 => $create_file_css_asset('public://css/29.css'),
          29 => $create_file_css_asset('public://css/30.css'),
          30 => $create_file_css_asset('public://css/31.css'),
          31 => $create_file_css_asset('public://css/32.css'),
        ],
        [
          0 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/1.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/2.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/3.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/4.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/5.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/6.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/7.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/8.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/9.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/10.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/11.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/12.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/13.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/14.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/15.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/16.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/17.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/18.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/19.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/20.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/21.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/22.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/23.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/24.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/25.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/26.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/27.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/28.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/29.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/30.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/31.css')) . '?0");
', 'all'),
          1 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/32.css')) . '?0");
', 'all'),
        ],
      ],
      // 32 file CSS assets with the same properties, except for the 10th and
      // 20th files, they have different 'media' properties. Expect 5 style
      // elements.
      5 => [
        [
          0 => $create_file_css_asset('public://css/1.css'),
          1 => $create_file_css_asset('public://css/2.css'),
          2 => $create_file_css_asset('public://css/3.css'),
          3 => $create_file_css_asset('public://css/4.css'),
          4 => $create_file_css_asset('public://css/5.css'),
          5 => $create_file_css_asset('public://css/6.css'),
          6 => $create_file_css_asset('public://css/7.css'),
          7 => $create_file_css_asset('public://css/8.css'),
          8 => $create_file_css_asset('public://css/9.css'),
          9 => $create_file_css_asset('public://css/10.css', 'screen'),
          10 => $create_file_css_asset('public://css/11.css'),
          11 => $create_file_css_asset('public://css/12.css'),
          12 => $create_file_css_asset('public://css/13.css'),
          13 => $create_file_css_asset('public://css/14.css'),
          14 => $create_file_css_asset('public://css/15.css'),
          15 => $create_file_css_asset('public://css/16.css'),
          16 => $create_file_css_asset('public://css/17.css'),
          17 => $create_file_css_asset('public://css/18.css'),
          18 => $create_file_css_asset('public://css/19.css'),
          19 => $create_file_css_asset('public://css/20.css', 'print'),
          20 => $create_file_css_asset('public://css/21.css'),
          21 => $create_file_css_asset('public://css/22.css'),
          22 => $create_file_css_asset('public://css/23.css'),
          23 => $create_file_css_asset('public://css/24.css'),
          24 => $create_file_css_asset('public://css/25.css'),
          25 => $create_file_css_asset('public://css/26.css'),
          26 => $create_file_css_asset('public://css/27.css'),
          27 => $create_file_css_asset('public://css/28.css'),
          28 => $create_file_css_asset('public://css/29.css'),
          29 => $create_file_css_asset('public://css/30.css'),
          30 => $create_file_css_asset('public://css/31.css'),
          31 => $create_file_css_asset('public://css/32.css'),
        ],
        [
          0 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/1.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/2.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/3.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/4.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/5.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/6.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/7.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/8.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/9.css')) . '?0");
', 'all'),
          1 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/10.css')) . '?0");
', 'screen'),
          2 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/11.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/12.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/13.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/14.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/15.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/16.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/17.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/18.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/19.css')) . '?0");
', 'all'),
          3 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/20.css')) . '?0");
', 'print'),
          4 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/21.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/22.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/23.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/24.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/25.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/26.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/27.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/28.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/29.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/30.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/31.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/32.css')) . '?0");
', 'all'),
        ],
      ],
      // 32 file CSS assets with the same properties, except for the 15th, which
      // has 'preprocess' = FALSE. Expect 1 link element and 2 style elements.
      6 => [
        [
          0 => $create_file_css_asset('public://css/1.css'),
          1 => $create_file_css_asset('public://css/2.css'),
          2 => $create_file_css_asset('public://css/3.css'),
          3 => $create_file_css_asset('public://css/4.css'),
          4 => $create_file_css_asset('public://css/5.css'),
          5 => $create_file_css_asset('public://css/6.css'),
          6 => $create_file_css_asset('public://css/7.css'),
          7 => $create_file_css_asset('public://css/8.css'),
          8 => $create_file_css_asset('public://css/9.css'),
          9 => $create_file_css_asset('public://css/10.css'),
          10 => $create_file_css_asset('public://css/11.css'),
          11 => $create_file_css_asset('public://css/12.css'),
          12 => $create_file_css_asset('public://css/13.css'),
          13 => $create_file_css_asset('public://css/14.css'),
          14 => $create_file_css_asset('public://css/15.css', 'all', FALSE),
          15 => $create_file_css_asset('public://css/16.css'),
          16 => $create_file_css_asset('public://css/17.css'),
          17 => $create_file_css_asset('public://css/18.css'),
          18 => $create_file_css_asset('public://css/19.css'),
          19 => $create_file_css_asset('public://css/20.css'),
          20 => $create_file_css_asset('public://css/21.css'),
          21 => $create_file_css_asset('public://css/22.css'),
          22 => $create_file_css_asset('public://css/23.css'),
          23 => $create_file_css_asset('public://css/24.css'),
          24 => $create_file_css_asset('public://css/25.css'),
          25 => $create_file_css_asset('public://css/26.css'),
          26 => $create_file_css_asset('public://css/27.css'),
          27 => $create_file_css_asset('public://css/28.css'),
          28 => $create_file_css_asset('public://css/29.css'),
          29 => $create_file_css_asset('public://css/30.css'),
          30 => $create_file_css_asset('public://css/31.css'),
          31 => $create_file_css_asset('public://css/32.css'),
        ],
        [
          0 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/1.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/2.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/3.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/4.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/5.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/6.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/7.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/8.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/9.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/10.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/11.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/12.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/13.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/14.css')) . '?0");
', 'all'),
          1 => $create_link_element(file_url_transform_relative(file_create_url('public://css/15.css')) . '?0'),
          2 => $create_style_element('
@import url("' . file_url_transform_relative(file_create_url('public://css/16.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/17.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/18.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/19.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/20.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/21.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/22.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/23.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/24.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/25.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/26.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/27.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/28.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/29.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/30.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/31.css')) . '?0");
@import url("' . file_url_transform_relative(file_create_url('public://css/32.css')) . '?0");
', 'all'),
        ],
      ],
    ];
  }

  /**
   * Tests CSS asset rendering.
   *
   * @dataProvider providerTestRender
   */
  public function testRender(array $css_assets, array $render_elements) {
    $this->state->expects($this->once())
      ->method('get')
      ->with('system.css_js_query_string')
      ->will($this->returnValue(NULL));
    $this->assertSame($render_elements, $this->renderer->render($css_assets));
  }

  /**
   * Tests a CSS asset group with the invalid 'type' => 'internal'.
   */
  public function testRenderInvalidType() {
    $this->state->expects($this->once())
      ->method('get')
      ->with('system.css_js_query_string')
      ->will($this->returnValue(NULL));
    $this->setExpectedException('Exception', 'Invalid CSS asset type.');

    $css_group = [
      'group' => 0,
      'type' => 'internal',
      'media' => 'all',
      'preprocess' => TRUE,
      'browsers' => [],
      'data' => 'http://example.com/popular.js'
    ];
    $this->renderer->render($css_group);
  }

}

/**
 * Temporary mock for file_create_url(), until that is moved into
 * Component/Utility.
 */
if (!function_exists('Drupal\Tests\Core\Asset\file_create_url')) {
  function file_create_url($uri) {
    return 'file_create_url:' . $uri;
  }
}

/**
 * Temporary mock of file_url_transform_relative, until that is moved into
 * Component/Utility.
 */
if (!function_exists('Drupal\Tests\Core\Asset\file_url_transform_relative')) {
  function file_url_transform_relative($uri) {
    return 'file_url_transform_relative:' . $uri;
  }
}

/**
 * CssCollectionRenderer uses file_create_url() & file_url_transform_relative(),
 * which *are* available when using the Simpletest test runner, but not when
 * using the PHPUnit test runner; hence this hack.
 */
namespace Drupal\Core\Asset;

if (!function_exists('Drupal\Core\Asset\file_create_url')) {

  /**
   * Temporary mock for file_create_url(), until that is moved into
   * Component/Utility.
   */
  function file_create_url($uri) {
    return \Drupal\Tests\Core\Asset\file_create_url($uri);
  }

}
if (!function_exists('Drupal\Core\Asset\file_url_transform_relative')) {

  /**
   * Temporary mock of file_url_transform_relative, until that is moved into
   * Component/Utility.
   */
  function file_url_transform_relative($uri) {
    return \Drupal\Tests\Core\Asset\file_url_transform_relative($uri);
  }

}
