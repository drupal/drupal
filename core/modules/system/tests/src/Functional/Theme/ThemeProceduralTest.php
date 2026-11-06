<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests low-level theme functions.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class ThemeProceduralTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'procedural_hook_theme';

  /**
   * Ensures preprocess functions run from procedural implementations.
   */
  #[IgnoreDeprecations]
  public function testPreprocess(): void {
    $this->expectUserDeprecationMessage('Code in procedural_hook_theme.theme is deprecated in drupal:11.5.0 and must be converted before drupal:13.0.0. Use classes instead. See https://www.drupal.org/node/3581222');
    $node_article_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_article_type->save();

    $node_basic_type = NodeType::create([
      'type' => 'basic',
      'name' => 'Basic',
    ]);
    $node_basic_type->save();

    $node = Node::create([
      'title' => 'placeholder_title',
      'type' => 'article',
      'uid' => 1,
    ]);
    $node->save();

    $node = Node::create([
      'title' => 'placeholder_title',
      'type' => 'basic',
      'uid' => 1,
    ]);
    $node->save();
    $this->drupalGet('node/1');
    $items = $this->cssSelect('.title');
    $this->assertEquals('Procedural Article Node Preprocess', $items[0]->getText());
    $this->drupalGet('node/2');
    $items = $this->cssSelect('.title');
    $this->assertEquals('Procedural Node Preprocess', $items[0]->getText());
  }

}
