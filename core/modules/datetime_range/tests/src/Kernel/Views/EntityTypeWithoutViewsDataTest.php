<?php

declare(strict_types=1);

namespace Drupal\Tests\datetime_range\Kernel\Views;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests datetime_range.module when an entity type provides no views data.
 */
#[Group('datetime')]
#[RunTestsInSeparateProcesses]
class EntityTypeWithoutViewsDataTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'node',
    'system',
    'taxonomy',
    'user',
    'views',
  ];

  /**
   * Tests the case when an entity type provides no views data.
   *
   * @see ::entityTypeAlter()
   */
  public function testEntityTypeWithoutViewsData(): void {
    $view_yaml = $this->getModulePath('taxonomy') . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY . '/views.view.taxonomy_term.yml';
    $values = Yaml::decode(file_get_contents($view_yaml));
    $this->assertEquals(SAVED_NEW, View::create($values)->save());
  }

  /**
   * Implements hook_entity_type_alter().
   *
   * @see ::testEntityTypeWithoutViewsData()
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types): void {
    // Inhibit views data for the 'taxonomy_term' entity type in order to cover
    // the case when an entity type provides no views data.
    // @see https://www.drupal.org/project/drupal/issues/2995578
    $entity_types['taxonomy_term']->setHandlerClass('views_data', NULL);
  }

}
