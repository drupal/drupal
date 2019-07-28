<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that cacheability metadata is only calculated when needed.
 *
 * @group views
 */
class CachableMetadataCalculationTest extends KernelTestBase {

  /**
   * The ID of the view used in the test.
   */
  const TEST_VIEW_ID = 'test_cacheable_metadata_calculation';

  /**
   * The ID of the module used in the test.
   */
  const TEST_MODULE = 'views_test_cacheable_metadata_calculation';

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'views', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['views']);
    $this->installEntitySchema('user');

    $this->state = $this->container->get('state');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
  }

  /**
   * Tests that cacheability metadata is only calculated when needed.
   *
   * Determining the cacheability of a view is an expensive operation since it
   * requires all Views plugins and handlers to be initialized. For efficiency
   * reasons this should only be done if a view is being saved (either through
   * the UI or the API). The cacheability metadata is then stored in the view
   * config and is ready to use at runtime.
   *
   * It should not be calculated when a view is enabled through installing a
   * module, or by syncing configuration.
   *
   * @see \Drupal\views\Entity\View::addCacheMetadata()
   */
  public function testCacheableMetadataCalculation() {
    // Enabling a module that contains a view should not cause the cacheability
    // metadata to be recalculated.
    $this->enableModules([self::TEST_MODULE]);
    $this->installConfig([self::TEST_MODULE]);
    $this->assertCachebleMetadataHasBeenCalculated(FALSE);

    // When a view is saved normally we have to recalculate the cacheability
    // metadata, since it is possible changes have been made to the view that
    // affect cacheability.
    $view = $this->entityTypeManager->getStorage('view')->load(self::TEST_VIEW_ID);
    $view->save();
    $this->assertCachebleMetadataHasBeenCalculated(TRUE);
    $this->resetState();

    // When a view is being saved due to config being synchronized, the
    // cacheability metadata doesn't change so it should not be recalculated.
    $view->setSyncing(TRUE);
    $view->save();
    $this->assertCachebleMetadataHasBeenCalculated(FALSE);
  }

  /**
   * Checks whether the view has calculated its cacheability metadata.
   *
   * @param bool $expected_result
   *   TRUE if it is expected that the cacheability metadata has been
   *   calculated. FALSE otherwise.
   */
  protected function assertCachebleMetadataHasBeenCalculated($expected_result) {
    $this->state->resetCache();
    $this->assertEquals($expected_result, $this->state->get('views_test_cacheable_metadata_has_been_accessed'));
  }

  /**
   * Resets the state so we are ready for a new test.
   */
  protected function resetState() {
    $this->state->set('views_test_cacheable_metadata_has_been_accessed', FALSE);
  }

}
