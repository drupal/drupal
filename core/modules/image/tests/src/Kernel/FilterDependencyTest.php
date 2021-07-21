<?php

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Database\Database;
use Drupal\filter\Entity\FilterFormat;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests text format and filter dependencies.
 *
 * @group filter
 */
class FilterDependencyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dblog',
    'filter',
    'image',
    'image_style_filter_test',
  ];

  /**
   * @covers \Drupal\image\Plugin\Filter\FilterImageStyle::calculateDependencies
   * @covers \Drupal\image\Plugin\Filter\FilterImageStyle::onDependencyRemoval
   * @covers \Drupal\filter\Entity\FilterFormat::onDependencyRemoval
   */
  public function testDependencyRemoval(): void {
    $this->installSchema('dblog', ['watchdog']);

    // Create two image styles and a text format.
    $style1 = ImageStyle::create(['name' => 'style1', 'label' => 'Style 1']);
    $style1->save();
    $style2 = ImageStyle::create(['name' => 'style2', 'label' => 'Style 2']);
    $style2->save();
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::create(['format' => 'format']);
    $format->save();

    $config = [
      'settings' => ['allowed_styles' => []],
      'status' => TRUE,
    ];

    // Add the enabled 'filter_image_style' filter with no 'allowed_styles'.
    $format->setFilterConfig('filter_image_style', $config);
    $format->save();

    // Check that no config dependencies were added.
    $this->assertEmpty($format->filters('filter_image_style')->getConfiguration()['settings']['allowed_styles']);
    $this->assertSame([
      'module' => [
        'image',
      ],
    ], $format->getDependencies());
    $this->assertTrue($format->filters('filter_image_style')->getConfiguration()['status']);

    // Add a dependency but disable the filter.
    $config['settings']['allowed_styles'][] = 'style1';
    $config['status'] = FALSE;
    $format->setFilterConfig('filter_image_style', $config);
    $format->save();

    // Check dependencies are added also for disabled filters.
    $this->assertSame(
      ['style1'],
      $format->filters('filter_image_style')->getConfiguration()['settings']['allowed_styles']
    );
    $this->assertSame([
      'config' => [
        'image.style.style1',
      ],
      'module' => [
        'image',
      ],
    ], $format->getDependencies());
    $this->assertFalse($format->filters('filter_image_style')->getConfiguration()['status']);

    // Re-enable the filter and add second dependency.
    $config['status'] = TRUE;
    $config['settings']['allowed_styles'] = [
      // Descending order to test if they're stored in ascending order.
      'style2',
      'style1',
    ];
    $format->setFilterConfig('filter_image_style', $config);
    $format->save();

    $this->assertSame([
      // The allowed styles list was ordered ascending by label.
      'style1',
      'style2',
    ], $format->filters('filter_image_style')->getConfiguration()['settings']['allowed_styles']);
    $this->assertSame([
      'config' => [
        'image.style.style1',
        'image.style.style2',
      ],
      'module' => [
        'image',
      ],
    ], $format->getDependencies());

    // Delete the first dependency and reload the entity.
    $style1->delete();
    $format = FilterFormat::load('format');

    // Check that the text format entity has been updated.
    $this->assertSame([
      'style2',
    ], $format->filters('filter_image_style')->getConfiguration()['settings']['allowed_styles']);
    $this->assertSame([
      'config' => [
        'image.style.style2',
      ],
      'module' => [
        'image',
      ],
    ], $format->getDependencies());

    // Delete the second dependency and reload the entity.
    $style2->delete();
    $format = FilterFormat::load('format');

    // Check that an unresolved removed dependency disables the filter.
    // @see \Drupal\image_style_filter_test\FilterTestImageStyle::onDependencyRemoval()
    $this->assertEmpty($format->filters('filter_image_style')->getConfiguration()['settings']['allowed_styles']);
    $this->assertEmpty($format->getDependencies());
    $this->assertFalse($format->filters('filter_image_style')->getConfiguration()['status']);

    // Check that the correct warning message has been logged.
    $arguments = ['@format' => 'format', '@filter' => 'filter_image_style'];
    $logged = (bool) Database::getConnection()->select('watchdog', 'w')
      ->fields('w', ['wid'])
      ->condition('type', 'filter')
      ->condition('message', "The '@format' filter '@filter' has been disabled because its configuration depends on removed dependencies.")
      ->condition('variables', serialize($arguments))
      ->execute()
      ->fetchAll();
    $this->assertTrue($logged);
  }

}
