<?php

namespace Drupal\Tests\layout_builder\Functional\Rest;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;

/**
 * Tests that default layout sections are not exposed via the REST API.
 *
 * @group layout_builder
 * @group rest
 */
class EntityDisplaySectionsTest extends LayoutRestTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $resourceConfigId = 'entity.entity_view_display';

  /**
   * Tests the normalization does not contain layout sections.
   */
  public function testLayoutEntityDisplay() {
    $display_id = 'node.bundle_with_section_field.default';
    $display = EntityViewDisplay::load($display_id);

    // Assert the display has 1 section.
    $this->assertCount(1, $display->getThirdPartySetting('layout_builder', 'sections'));
    $response = $this->request(
      'GET',
      Url::fromRoute(
        'rest.entity.entity_view_display.GET',
        ['entity_view_display' => 'node.bundle_with_section_field.default'])
    );
    $this->assertResourceResponse(
      200,
      FALSE,
      $response,
      [
        'config:core.entity_view_display.node.bundle_with_section_field.default',
        'config:rest.resource.entity.entity_view_display',
        'config:rest.settings',
        'http_response',
      ],
      [
        'user.permissions',
      ],
      FALSE,
      'MISS'
    );
    $response_data = $this->getDecodedContents($response);
    $this->assertSame($display_id, $response_data['id']);
    // Ensure the sections are not present in the serialized data, but other
    // Layout Builder data is.
    $this->assertArrayHasKey('layout_builder', $response_data['third_party_settings']);
    $this->assertArrayNotHasKey('sections', $response_data['third_party_settings']['layout_builder']);
    $this->assertEquals(['enabled' => TRUE, 'allow_custom' => TRUE], $response_data['third_party_settings']['layout_builder']);
  }

}
