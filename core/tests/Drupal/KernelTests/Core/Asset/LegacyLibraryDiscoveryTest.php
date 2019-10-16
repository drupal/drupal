<?php

namespace Drupal\KernelTests\Core\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that deprecated asset libraries trigger a deprecation error.
 *
 * @group Asset
 * @group legacy
 */
class LegacyLibraryDiscoveryTest extends KernelTestBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->libraryDiscovery = $this->container->get('library.discovery');
  }

  /**
   * Tests that the jquery.ui.accordion library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.accordion" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiAccordion() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.accordion');
  }

  /**
   * Tests that the jquery.ui.checkboxradio library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.checkboxradio" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiCheckboxradio() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.checkboxradio');
  }

  /**
   * Tests that the jquery.ui.controlgroup library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.controlgroup" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiControlgroup() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.controlgroup');
  }

  /**
   * Tests that the jquery.ui.droppable library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.droppable" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiDroppable() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.droppable');
  }

  /**
   * Tests that the jquery.ui.effects.core library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.core" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsCore() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.core');
  }

  /**
   * Tests that the jquery.ui.effects.blind library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.blind" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsBlind() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.blind');
  }

  /**
   * Tests that the jquery.ui.effects.bounce library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.bounce" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsBounce() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.bounce');
  }

  /**
   * Tests that the jquery.ui.effects.clip library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.clip" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsClip() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.clip');
  }

  /**
   * Tests that the jquery.ui.effects.drop library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.drop" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsDrop() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.drop');
  }

  /**
   * Tests that the jquery.ui.effects.explode library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.explode" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsExplode() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.explode');
  }

  /**
   * Tests that the jquery.ui.effects.fade library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.fade" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsFade() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.fade');
  }

  /**
   * Tests that the jquery.ui.effects.fold library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.fold" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsFold() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.fold');
  }

  /**
   * Tests that the jquery.ui.effects.highlight library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.highlight" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsHighlight() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.highlight');
  }

  /**
   * Tests that the jquery.ui.effects.puff library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.puff" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsPuff() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.puff');
  }

  /**
   * Tests that the jquery.ui.effects.pulsate library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.pulsate" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsPulsate() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.pulsate');
  }

  /**
   * Tests that the jquery.ui.effects.scale library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.scale" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsScale() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.scale');
  }

  /**
   * Tests that the jquery.ui.effects.shake library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.shake" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsShake() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.shake');
  }

  /**
   * Tests that the jquery.ui.effects.size library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.size" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsSize() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.size');
  }

  /**
   * Tests that the jquery.ui.effects.slide library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.slide" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsSlide() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.slide');
  }

  /**
   * Tests that the jquery.ui.effects.transfer library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.effects.transfer" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiEffectsTransfer() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.effects.transfer');
  }

  /**
   * Tests that the jquery.ui.progressbar library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.progressbar" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiProgressbar() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.progressbar');
  }

  /**
   * Tests that the jquery.ui.selectable library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.selectable" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiSelectable() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.selectable');
  }

  /**
   * Tests that the jquery.ui.selectmenu library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.selectmenu" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiSelectmenu() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.selectmenu');
  }

  /**
   * Tests that the jquery.ui.slider library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.slider" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiSlider() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.slider');
  }

  /**
   * Tests that the jquery.ui.spinner library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.spinner" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiSpinner() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.spinner');
  }

  /**
   * Tests that the jquery.ui.tabs library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.tabs" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiTabs() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.tabs');
  }

  /**
   * Tests that the jquery.ui.tooltip library is deprecated.
   *
   * @expectedDeprecation The "core/jquery.ui.tooltip" asset library is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. See https://www.drupal.org/node/3067969
   * @doesNotPerformAssertions
   */
  public function testJqueryUiTooltip() {
    $this->libraryDiscovery->getLibraryByName('core', 'jquery.ui.tooltip');
  }

}
