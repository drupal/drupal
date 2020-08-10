<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

/**
 * Tests the representative node relationship for terms.
 *
 * @group taxonomy
 */
class TaxonomyDefaultArgumentTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['taxonomy_default_argument_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests escaping of page title when the taxonomy plugin provides it.
   */
  public function testTermTitleEscaping() {
    $this->term1->setName('<em>Markup</em>')->save();
    $this->drupalGet('taxonomy_default_argument_test/' . $this->term1->id());
    $this->assertSession()->assertEscaped($this->term1->label());
  }

}
