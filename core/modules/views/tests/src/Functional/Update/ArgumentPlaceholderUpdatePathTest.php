<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the argument placeholder update path.
 *
 * @see views_update_8002()
 *
 * @group views
 */
class ArgumentPlaceholderUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/argument-placeholder.php'
    ];
  }

  /**
   * Ensures that %1 and !1 are converted to twig tokens in existing views.
   */
  public function testArgumentPlaceholderUpdate() {
    $this->runUpdates();
    $view = View::load('test_token_view');

    $data = $view->toArray();
    $this->assertEqual('{{ arguments.nid }}-test-class-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['style']['options']['col_class_custom']);
    $this->assertEqual('{{ arguments.nid }}-test-class-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['style']['options']['row_class_custom']);
    $this->assertEqual('{{ arguments.nid }}-description-{{ raw_arguments.nid }}', $data['display']['feed_1']['display_options']['style']['options']['description']);
    $this->assertEqual('{{ arguments.nid }}-custom-text-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['fields']['title']['alter']['text']);
    $this->assertEqual('test_token_view {{ arguments.nid }} {{ raw_arguments.nid }}', $data['display']['default']['display_options']['title']);
    $this->assertEqual('{{ arguments.nid }}-custom-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['header']['area_text_custom']['content']);
    $this->assertEqual('{{ arguments.nid }}-text-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['footer']['area']['content']['value']);
    $this->assertEqual("Displaying @start - @end of @total\n\n{{ arguments.nid }}-result-{{ raw_arguments.nid }}", $data['display']['default']['display_options']['empty']['result']['content']);
    $this->assertEqual('{{ arguments.nid }}-title-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['empty']['title']['title']);
    $this->assertEqual('{{ arguments.nid }}-entity-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['empty']['entity_node']['target']);
    $this->assertEqual('{{ arguments.nid }} title {{ raw_arguments.nid }}', $data['display']['default']['display_options']['arguments']['nid']['title']);
    $this->assertEqual('{{ arguments.nid }} exception-title {{ raw_arguments.nid }}', $data['display']['default']['display_options']['arguments']['nid']['exception']['title']);
    $this->assertEqual('{{ arguments.nid }}-more-text-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['use_more_text']);
    $this->assertEqual('{{ arguments.nid }}-custom-url-{{ raw_arguments.nid }}', $data['display']['default']['display_options']['link_url']);
  }

}
