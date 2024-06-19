<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests update of media.settings:iframe_domain if it's still the default of "".
 *
 * @group system
 * @covers \media_post_update_set_blank_iframe_domain_to_null
 */
class MediaSettingsDefaultIframeDomainUpdateTest extends UpdatePathTestBase {

  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/media.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Because the test manually installs media module, the entity type config
    // must be manually installed similar to kernel tests.
    $entity_type_manager = \Drupal::entityTypeManager();
    $media = $entity_type_manager->getDefinition('media');
    \Drupal::service('entity_type.listener')->onEntityTypeCreate($media);
    $media_type = $entity_type_manager->getDefinition('media_type');
    \Drupal::service('entity_type.listener')->onEntityTypeCreate($media_type);
  }

  /**
   * Tests update of media.settings:iframe_domain.
   */
  public function testUpdate(): void {
    $iframe_domain_before = $this->config('media.settings')->get('iframe_domain');
    $this->assertSame('', $iframe_domain_before);

    $this->runUpdates();

    $iframe_domain_after = $this->config('media.settings')->get('iframe_domain');
    $this->assertNull($iframe_domain_after);
  }

}
