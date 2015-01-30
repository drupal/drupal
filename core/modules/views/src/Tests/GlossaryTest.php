<?php

/**
 * @file
 * Definition of Drupal\views\Tests\GlossaryTest.
 */

namespace Drupal\views\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * Tests glossary functionality of views.
 *
 * @group views
 */
class GlossaryTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * Tests the default glossary view.
   */
  public function testGlossaryView() {
    // create a contentype and add some nodes, with a non random title.
    $type = $this->drupalCreateContentType();
    $nodes_per_char = array(
      'd' => 1,
      'r' => 4,
      'u' => 10,
      'p' => 2,
      'a' => 3,
      'l' => 6,
    );
    foreach ($nodes_per_char as $char => $count) {
      $setting = array(
        'type' => $type->id()
      );
      for ($i = 0; $i < $count; $i++) {
        $node = $setting;
        $node['title'] = $char . $this->randomString(3);
        $this->drupalCreateNode($node);
      }
    }

    // Execute glossary view
    $view = Views::getView('glossary');
    $view->setDisplay('attachment_1');
    $view->executeDisplay('attachment_1');

    // Check that the amount of nodes per char.
    foreach ($view->result as $item) {
      $this->assertEqual($nodes_per_char[$item->title_truncated], $item->num_records);
    }

    // Enable the glossary to be displayed.
    $view->storage->enable()->save();
    // Check the actual page response.
    $this->drupalGet('glossary');
    $this->assertResponse(200);

    foreach ($nodes_per_char as $char => $count) {
      $href = Url::fromRoute('view.glossary.page_1', ['arg_0' => $char])->toString();
      $label = Unicode::strtoupper($char);
      // Get the summary link for a certain character. Filter by label and href
      // to ensure that both of them are correct.
      $result = $this->xpath('//a[contains(@href, :href) and normalize-space(text())=:label]/..', array(':href' => $href, ':label' => $label));
      $this->assertTrue(count($result));
      // The rendered output looks like "| (count)" so let's figure out the int.
      $result_count = trim(str_replace(array('|', '(', ')'), '', (string) $result[0]));
      $this->assertEqual($result_count, $count, 'The expected number got rendered.');
    }
  }

}
