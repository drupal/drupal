<?php

declare(strict_types=1);

namespace Drupal\TestTools;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Provides handlers for the Symfony VarDumper to work within tests.
 *
 * This allows the dump() function to produce output on the terminal without
 * causing PHPUnit to complain.
 */
class TestVarDumper {

  /**
   * A CLI handler for \Symfony\Component\VarDumper\VarDumper.
   */
  public static function cliHandler($var) {
    $cloner = new VarCloner();
    $dumper = new CliDumper();
    fwrite(STDERR, "\n");
    $dumper->setColors(TRUE);
    $dumper->dump(
      $cloner->cloneVar($var),
      function ($line, $depth, $indent_pad) {
        // A negative depth means "end of dump".
        if ($depth >= 0) {
          // Adds a two spaces indentation to the line.
          fwrite(STDERR, str_repeat($indent_pad, $depth) . $line . "\n");
        }
      }
    );
  }

  /**
   * A HTML handler for \Symfony\Component\VarDumper\VarDumper.
   */
  public static function htmlHandler($var) {
    $cloner = new VarCloner();
    $dumper = new HtmlDumper();
    $dumper->dump($cloner->cloneVar($var));
  }

}
