<?php

namespace Drupal\Tests\tour\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Tour's backwards compatible markup and legacy config.
 *
 * @group tour
 * @group legacy
 */
class TourLegacyTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tour',
    'tour_legacy_test',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'access toolbar',
      'access tour',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Confirms backwards compatible markup.
   *
   * @param string $path
   *   The path to check.
   * @param string $theme
   *   The theme used by the tests.
   *
   * @dataProvider providerTestTourTipMarkup
   */
  public function testTourTipMarkup(string $path, string $theme = NULL) {
    // Install the specified theme and make it default if that is not already
    // the case.
    if ($theme) {
      $theme_manager = $this->container->get('theme.manager');
      $this->container->get('theme_installer')->install([$theme], TRUE);

      $system_theme_config = $this->container->get('config.factory')->getEditable('system.theme');
      $system_theme_config
        ->set('default', $theme)
        ->save();
      $this->rebuildAll();
      $this->assertSame($theme, $theme_manager->getActiveTheme()->getName());
    }

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet($path);

    $assert_session->waitForElementVisible('css', '#toolbar-tab-tour button');
    $page->find('css', '#toolbar-tab-tour button')->press();
    $this->assertToolTipMarkup(0, 'top');
    $page->find('css', '.joyride-tip-guide[data-index="0"]')->clickLink('Next');
    $this->assertToolTipMarkup(1, '', 'image');
    $page->find('css', '.joyride-tip-guide[data-index="1"]')->clickLink('Next');
    $this->assertToolTipMarkup(2, 'top', 'body');
    $tip_content = $assert_session->waitForElementVisible('css', '.joyride-tip-guide[data-index="2"] .joyride-content-wrapper');

    $additional_paragraph = $tip_content->find('css', '.tour-tip-body + p');
    $this->assertNotNull($additional_paragraph, 'Tip 3 has an additional paragraph that is a sibling to the main paragraph.');
    $additional_list = $tip_content->find('css', '.tour-tip-body + p + ul');
    $this->assertNotNull($additional_list, 'Tip 3 has an additional unordered list that is a sibling to the main paragraph.');
  }

  /**
   * Asserts the markup structure of a tip.
   *
   * @param int $index
   *   The position of the tip within the tour.
   * @param string $nub_position
   *   The expected position of the nub arrow.
   * @param string $joyride_content_container_name
   *   For identifying classnames specific to a tip type.
   *
   * @internal
   */
  private function assertToolTipMarkup(int $index, string $nub_position, string $joyride_content_container_name = 'body'): void {
    $assert_session = $this->assertSession();
    $tip = $assert_session->waitForElementVisible('css', ".joyride-tip-guide[data-index=\"$index\"]");
    $this->assertNotNull($tip, 'The tour tip element is present.');

    $nub = $tip->find('css', ".joyride-tip-guide[data-index=\"$index\"] > .joyride-nub");
    $this->assertNotNull($nub, 'The nub element is present.');
    if (!empty($nub_position)) {
      $this->assertTrue($nub->hasClass($nub_position), 'The nub has a class that indicates its configured position.');
    }

    $content_wrapper = $tip->find('css', '.joyride-nub + .joyride-content-wrapper');
    $this->assertNotNull($content_wrapper, 'The joyride content wrapper exists, and is the next sibling of the nub.');

    $label = $tip->find('css', '.joyride-content-wrapper > h2.tour-tip-label:first-child');
    $this->assertNotNull($label, 'The tour tip label is an h2, and is the first child of the content wrapper.');

    $tip_content = $content_wrapper->find('css', "h2.tour-tip-label + p.tour-tip-$joyride_content_container_name");
    $this->assertNotNull($tip_content, 'The tip\'s main paragraph is the next sibling of the label, and has the expected wrapper class.');

    $tour_progress = $content_wrapper->find('css', "h2.tour-tip-label + p.tour-tip-$joyride_content_container_name ~ div.tour-progress");
    $this->assertNotNull($tour_progress, 'The div containing tour progress info is present, and is the next sibling of the main paragraph.');

    $next_item = $content_wrapper->find('css', ".tour-progress + a.joyride-next-tip.button.button--primary");
    $this->assertNotNull($next_item, 'The "Next" link is present, and the next sibling of the div containing progress info.');

    $close_tour = $content_wrapper->find('css', ".joyride-content-wrapper > a.joyride-close-tip:last-child");
    $this->assertNotNull($close_tour, 'The "Close" link is present, is an immediate child of the content wrapper, and is the last child.');
  }

  /**
   * Data Provider.
   *
   * @return \string[][]
   *   An array with two potential items:
   *   - The different path the test will run on.
   *   - The active theme when running the tests.
   */
  public function providerTestTourTipMarkup() {
    return [
      'Using the the deprecated TipPlugin with Stable theme' => ['tour-test-legacy'],
      'Using current TourTipPlugin with Stable theme' => ['tour-test-1'],
      'Using the the deprecated TipPlugin with Stable 9 theme' => ['tour-test-legacy', 'stable9'],
      'Using current TourTipPlugin with Stable 9 theme' => ['tour-test-1', 'stable9'],
    ];
  }

}
