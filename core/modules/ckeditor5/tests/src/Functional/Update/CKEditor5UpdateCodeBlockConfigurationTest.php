<?php

namespace Drupal\Tests\ckeditor5\Functional\Update;

use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @covers ckeditor5_post_update_code_block
 * @group Update
 * @group ckeditor5
 */
class CKEditor5UpdateCodeBlockConfigurationTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
    ];
  }

  /**
   * Ensure default configuration for the CKEditor 5 codeBlock plugin is added.
   */
  public function testUpdateCodeBlockConfigurationPostUpdate(): void {
    $editor = Editor::load('full_html');
    $settings = $editor->getSettings();
    $this->assertArrayNotHasKey('ckeditor5_codeBlock', $settings['plugins']);

    $this->runUpdates();

    $editor = Editor::load('full_html');
    $settings = $editor->getSettings();
    $this->assertArrayHasKey('ckeditor5_codeBlock', $settings['plugins']);
    // @see \Drupal\ckeditor5\Plugin\CKEditor5Plugin\CodeBlock::defaultConfiguration()
    $this->assertSame([
      'languages' => [
        ['label' => 'Plain text', 'language' => 'plaintext'],
        ['label' => 'C', 'language' => 'c'],
        ['label' => 'C#', 'language' => 'cs'],
        ['label' => 'C++', 'language' => 'cpp'],
        ['label' => 'CSS', 'language' => 'css'],
        ['label' => 'Diff', 'language' => 'diff'],
        ['label' => 'HTML', 'language' => 'html'],
        ['label' => 'Java', 'language' => 'java'],
        ['label' => 'JavaScript', 'language' => 'javascript'],
        ['label' => 'PHP', 'language' => 'php'],
        ['label' => 'Python', 'language' => 'python'],
        ['label' => 'Ruby', 'language' => 'ruby'],
        ['label' => 'TypeScript', 'language' => 'typescript'],
        ['label' => 'XML', 'language' => 'xml'],
      ],
    ], $settings['plugins']['ckeditor5_codeBlock']);
  }

}
