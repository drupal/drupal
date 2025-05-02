<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Behat\Mink\Element\NodeElement;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;

/**
 * Tests code block configured languages are respected.
 *
 * @group ckeditor5
 * @internal
 */
class CKEditor5CodeSyntaxTest extends CKEditor5TestBase {

  use CKEditor5TestTrait;

  /**
   * Tests code block configured languages are respected.
   */
  public function testCKEditor5CodeSyntax(): void {
    $this->addNewTextFormat();
    /** @var \Drupal\editor\Entity\Editor $editor */
    $editor = Editor::load('ckeditor5');
    $editor->setSettings([
      'toolbar' => [
        'items' => [
          'codeBlock',
        ],
      ],
      'plugins' => [
        'ckeditor5_codeBlock' => [
          'languages' => [
            ['label' => 'Twig', 'language' => 'twig'],
            ['label' => 'YML', 'language' => 'yml'],
          ],
        ],
      ],
    ])->save();
    $this->drupalGet('/node/add/page');

    $this->waitForEditor();
    // Open code block dropdown, and verify that correct languages are present.
    $assertSession = $this->assertSession();
    $page = $this->getSession()->getPage();
    $page->find('css', '.ck-code-block-dropdown .ck-dropdown__button .ck-splitbutton__arrow')->click();
    $codeBlockOptionsSelector = '.ck-code-block-dropdown .ck-dropdown__panel .ck-list__item .ck-button__label';
    $assertSession->waitForElementVisible('css', $codeBlockOptionsSelector);
    $codeBlockOptions = $page->findAll('css', $codeBlockOptionsSelector);
    $this->assertCount(2, $codeBlockOptions);
    $this->assertEquals([
      'Twig',
      'YML',
    ], \array_map(static fn (NodeElement $el) => $el->getText(), $codeBlockOptions));
  }

}
