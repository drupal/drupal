<?php

namespace Drupal\Tests\responsive_image\Functional\Update;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Serialization\Yaml;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests responsive image module updates.
 *
 * @group responsive_image
 * @group legacy
 */
class ResponsiveImageUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8-rc1.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.responsive_image-enabled.php',
    ];
  }

  /**
   * Tests post-update responsive_image_post_update_dependency().
   *
   * @see responsive_image_post_update_dependency()
   */
  public function testPostUpdateDependency() {
    // Installing the 'wide' responsive image style.
    $wide_image_style = Yaml::decode(file_get_contents(__DIR__ . '/../../../../../../profiles/standard/config/optional/responsive_image.styles.wide.yml'));
    $this->config('responsive_image.styles.wide')->setData($wide_image_style)->save(TRUE);

    // Change 'field_image' formatter to a responsive image formatter.
    $options = [
      'type' => 'responsive_image',
      'label' => 'hidden',
      'settings' => ['responsive_image_style' => 'wide', 'image_link' => ''],
      'third_party_settings' => [],
    ];
    $display = $this->config('core.entity_view_display.node.article.default');
    $display->set('content.field_image', $options)->save(TRUE);

    // Check that there's no dependency to 'responsive_image.styles.wide'.
    $dependencies = $display->get('dependencies.config') ?: [];
    $this->assertFalse(in_array('responsive_image.styles.wide', $dependencies));

    // Run updates.
    $this->runUpdates();

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = EntityViewDisplay::load('node.article.default');
    $dependencies = $view_display->getDependencies() + ['config' => []];
    // Check that post-update added a 'responsive_image.styles.wide' dependency.
    $this->assertTrue(in_array('responsive_image.styles.wide', $dependencies['config']));
  }

}
