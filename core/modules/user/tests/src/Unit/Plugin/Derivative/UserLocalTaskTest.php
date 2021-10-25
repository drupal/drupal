<?php

namespace Drupal\Tests\user\Unit\Plugin\Derivative;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Plugin\Derivative\UserLocalTask;

/**
 * Tests the local tasks deriver class.
 *
 * @coversDefaultClass \Drupal\user\Plugin\Derivative\UserLocalTask
 * @group user
 */
class UserLocalTaskTest extends UnitTestCase {

  /**
   * The local tasks deriver.
   *
   * @var \Drupal\user\Plugin\Derivative\UserLocalTask
   */
  protected $deriver;

  protected function setUp(): void {
    parent::setUp();

    $prophecy = $this->prophesize(EntityTypeInterface::class);
    $prophecy->get('field_ui_base_route')->willReturn(NULL);
    $entity_no_bundle_type = $prophecy->reveal();

    $prophecy = $this->prophesize(EntityTypeInterface::class);
    $prophecy->get('field_ui_base_route')->willReturn('field_ui.base_route');
    $prophecy->getBundleEntityType()->willReturn(NULL);
    $entity_bundle_type = $prophecy->reveal();

    $prophecy = $this->prophesize(EntityTypeInterface::class);
    $prophecy->get('field_ui_base_route')->willReturn('field_ui.base_route');
    $prophecy->getBundleEntityType()->willReturn('field_ui_bundle_type');
    $field_ui_bundle_type = $prophecy->reveal();

    $prophecy = $this->prophesize(EntityTypeManagerInterface::class);
    $prophecy->getDefinitions()->willReturn([
      'case_no_bundle_type' => $entity_no_bundle_type,
      'case_bundle_type' => $entity_bundle_type,
      'case_field_ui' => $field_ui_bundle_type,
    ]);
    $entity_type_manager = $prophecy->reveal();

    $this->deriver = new UserLocalTask($entity_type_manager, $this->getStringTranslationStub());
  }

  /**
   * Tests the derivatives generated for local tasks.
   *
   * @covers \Drupal\user\Plugin\Derivative\UserLocalTask::getDerivativeDefinitions()
   */
  public function testGetDerivativeDefinitions() {
    $expected = [
      'permissions_field_ui_bundle_type' => [
        'route_name' => 'entity.field_ui_bundle_type.permission_form',
        'weight' => 10,
        'title' => $this->getStringTranslationStub()->translate('Manage permissions'),
        'base_route' => 'field_ui.base_route',
      ],
    ];
    $this->assertEquals($expected, $this->deriver->getDerivativeDefinitions([]));
  }

}
