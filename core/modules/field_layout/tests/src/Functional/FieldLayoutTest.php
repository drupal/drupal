<?php

namespace Drupal\Tests\field_layout\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests using field layout for entity displays.
 *
 * @group field_layout
 */
class FieldLayoutTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_layout', 'field_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType([
      'type' => 'article',
    ]);
    $this->createNode([
      'type' => 'article',
      'title' => 'The node title',
      'body' => [[
        'value' => 'The node body',
      ]],
    ]);
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer nodes',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'view the administration theme',
    ]));
  }

  /**
   * Tests an entity type that has fields shown by default.
   */
  public function testNodeView() {
    // By default, the one-column layout is used.
    $this->drupalGet('node/1');
    $this->assertSession()->elementExists('css', '.layout--onecol');
    $this->assertSession()->elementExists('css', '.layout-region--content .field--name-body');

    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertEquals(['Content', 'Disabled'], $this->getRegionTitles());
    $this->assertSession()->optionExists('fields[body][region]', 'content');
  }

  /**
   * Gets the region titles on the page.
   *
   * @return string[]
   *   An array of region titles.
   */
  protected function getRegionTitles() {
    $region_titles = [];
    $region_title_elements = $this->getSession()->getPage()->findAll('css', '.region-title td');
    /** @var \Behat\Mink\Element\NodeElement[] $region_title_elements */
    foreach ($region_title_elements as $region_title_element) {
      $region_titles[] = $region_title_element->getText();
    }
    return $region_titles;
  }

}
