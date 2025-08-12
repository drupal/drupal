<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Form\FormState;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\Tableselect
 * @group Render
 */
class TableSelectTest extends UnitTestCase {

  /**
   * @covers ::processTableselect
   */
  public function testProcessTableselectWithLinkTitle(): void {
    $element = [];
    $form_state = new FormState();
    $complete_form = [];

    $element_object = new Tableselect([], 'table_select', []);
    $info = $element_object->getInfo();
    $element += $info;

    $element['#value'] = 0;

    $element['#options'][] = [
      'title' => new Link('my-text', Url::fromRoute('<front>')),
    ];

    $element['#attributes'] = [];

    Tableselect::processTableselect($element, $form_state, $complete_form);

    $this->assertEquals('', $element[0]['#title']);
  }

  /**
   * @covers ::processTableselect
   */
  public function testProcessTableselectWithStringTitle(): void {
    $element = [];
    $form_state = new FormState();
    $complete_form = [];

    $element_object = new Tableselect([], 'table_select', []);
    $info = $element_object->getInfo();
    $element += $info;

    $element['#value'] = 0;

    $element['#options'][] = [
      'title' => ['data' => ['#title' => 'Static title']],
    ];

    $element['#attributes'] = [];

    Tableselect::processTableselect($element, $form_state, $complete_form);

    $this->assertEquals(new TranslatableMarkup('Update @title', ['@title' => 'Static title']), $element[0]['#title']);
  }

}
