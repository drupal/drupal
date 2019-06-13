<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for limit operators feature.
 *
 * @see views_post_update_limit_operator_defaults()
 *
 * @group Update
 * @group legacy
 */
class LimitOperatorsDefaultsTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/limit-exposed-operators.php',
    ];
  }

  /**
   * Tests that default settings for limit operators are present.
   */
  public function testViewsPostUpdateLimitOperatorsDefaultValues() {
    // Load and initialize our test view.
    $view = View::load('test_exposed_filters');
    $data = $view->toArray();

    // Check that the filters have no defaults values to limit operators.
    $title_filter = $data['display']['default']['display_options']['filters']['title']['expose'];
    $this->assertArrayNotHasKey('operator_limit_selection', $title_filter);
    $this->assertArrayNotHasKey('operator_list', $title_filter);

    $created_filter = $data['display']['default']['display_options']['filters']['created']['expose'];
    $this->assertArrayNotHasKey('operator_limit_selection', $created_filter);
    $this->assertArrayNotHasKey('operator_list', $created_filter);

    $this->runUpdates();

    // Load and initialize our test view.
    $view = View::load('test_exposed_filters');
    $data = $view->toArray();

    // Check that the filters have defaults values to limit operators.
    $title_filter = $data['display']['default']['display_options']['filters']['title']['expose'];
    $this->assertIdentical(FALSE, $title_filter['operator_limit_selection']);
    $this->assertIdentical([], $title_filter['operator_list']);

    $created_filter = $data['display']['default']['display_options']['filters']['created']['expose'];
    $this->assertIdentical(FALSE, $created_filter['operator_limit_selection']);
    $this->assertIdentical([], $created_filter['operator_list']);
  }

}
