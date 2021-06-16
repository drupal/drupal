<?php

namespace Drupal\Tests\Core\Theme;

use Drupal\Tests\UnitTestCase;

/**
 * Confirms that certain Classy templates have identical equivalents in Stable.
 *
 * @group Theme
 */
class ClassyTemplatesIdenticalToStableTest extends UnitTestCase {

  /**
   * Confirms that certain Classy templates have equivalents in Stable.
   *
   * @dataProvider providerTestStableTemplatesIdenticalToClassy
   *
   * @param string $template
   *   The template file to test.
   */
  public function testStableTemplatesIdenticalToClassy($template) {
    $stable_template = $this->root . '/core/themes/stable/templates' . $template;
    $classy_template = $this->root . '/core/themes/classy/templates' . $template;
    $this->assertFileExists($stable_template);
    $this->assertFileExists($classy_template);
    $this->assertSame(md5_file($stable_template), md5_file($classy_template), 'The templates should have the same checksums.');
  }

  /**
   * A list of the Classy templates that have identical copies in Stable.
   */
  public function providerTestStableTemplatesIdenticalToClassy() {
    return [
      ['/content-edit/file-upload-help.html.twig'],
      ['/content-edit/file-widget-multiple.html.twig'],
      ['/field/image-formatter.html.twig'],
      ['/field/image-style.html.twig'],
      ['/form/checkboxes.html.twig'],
      ['/form/confirm-form.html.twig'],
      ['/form/container.html.twig'],
      ['/form/dropbutton-wrapper.html.twig'],
      ['/form/field-multiple-value-form.html.twig'],
      ['/form/form.html.twig'],
      ['/form/form-element-label.html.twig'],
      ['/form/input.html.twig'],
      ['/form/select.html.twig'],
      ['/navigation/links.html.twig'],
      ['/navigation/menu-local-action.html.twig'],
      ['/navigation/pager.html.twig'],
      ['/navigation/vertical-tabs.html.twig'],
      ['/views/views-view-grid.html.twig'],
      ['/views/views-view-list.html.twig'],
      ['/views/views-view-mapping-test.html.twig'],
      ['/views/views-view-opml.html.twig'],
      ['/views/views-view-row-opml.html.twig'],
      ['/views/views-view-rss.html.twig'],
      ['/views/views-view-unformatted.html.twig'],
    ];
  }

}
