<?php

namespace Drupal\Tests\user\Kernel\Views;

use Drupal\KernelTests\KernelTestBase;

/**
 * Contains tests related to the views data for the user entity type.
 *
 * @group user
 *
 * @see \Drupal\user\UserViewsData
 */
class UserViewsDataTest extends KernelTestBase {

  /**
   * The views data service.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->viewsData = $this->container->get('views.views_data');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
  }

  /**
   * Tests if user views data object doesn't contain pass field.
   */
  public function testUserPasswordFieldNotAvailableToViews() {
    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions('user');
    $this->assertArrayHasKey('pass', $field_definitions);
    $this->assertArrayNotHasKey('pass', $this->viewsData->get('users_field_data'));
  }

}
