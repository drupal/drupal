<?php

namespace Drupal\ckeditor\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to add style sheets to a CKEditor instance.
 */
class AddStyleSheetCommand implements CommandInterface {

  /**
   * The CKEditor instance ID.
   *
   * @var string
   */
  protected $editorId;

  /**
   * The style sheet URLs to add to the CKEditor instance.
   *
   * @var string[]
   */
  protected $styleSheets = [];

  /**
   * AddStyleSheetCommand constructor.
   *
   * @param string $editor_id
   *   The CKEditor instance ID.
   * @param string[] $stylesheets
   *   The style sheet URLs to add to the CKEditor instance.
   */
  public function __construct($editor_id, array $stylesheets = []) {
    $this->editorId = $editor_id;
    $this->styleSheets = $stylesheets;
  }

  /**
   * Adds a style sheet to the CKEditor instance.
   *
   * @param string $stylesheet
   *   The style sheet URL.
   *
   * @return $this
   *   The called object, for chaining.
   */
  public function addStyleSheet($stylesheet) {
    $this->styleSheets[] = $stylesheet;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'ckeditor_add_stylesheet',
      'editor_id' => $this->editorId,
      'stylesheets' => $this->styleSheets,
    ];
  }

}
