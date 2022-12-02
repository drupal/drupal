<?php

namespace Drupal\Tests\search\Kernel;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\search\Entity\SearchPage;

/**
 * Tests validation of search_page entities.
 *
 * @group search
 */
class SearchPageValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = SearchPage::create([
      'id' => 'test',
      'label' => 'Test',
      'plugin' => 'user_search',
    ]);
    $this->entity->save();
  }

}
