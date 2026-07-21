<?php

/**
 * @file
 * Twig CS Fixer configuration for Drupal.
 *
 * @see https://github.com/VincentLanglet/Twig-CS-Fixer/blob/main/docs/configuration.md
 */

use Drupal\Core\Template\TwigTransTokenParser;
use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\Literal\CompactHashRule;
use TwigCsFixer\Rules\Whitespace\IndentRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

$config = new Config();
$config->setCacheFile('./core/.twigcsfixercache');
$config->addTokenParser(new TwigTransTokenParser());

// Define which files to exclude from linting.
$finder = new Finder();
$finder->exclude('tests');
$config->setFinder($finder);

$ruleset = new Ruleset();
// Add Twig CS Fixer's defaults, which include the Twig coding standards.
// @see https://twig.symfony.com/doc/3.x/coding_standards.html
$ruleset->addStandard(new TwigCsFixer());

// Customize the above standards for Drupal.
$ruleset->overrideRule(new CompactHashRule(TRUE));
$ruleset->overrideRule(new IndentRule(spaceRatio: 2));

$config->allowNonFixableRules();
$config->setRuleset($ruleset);

return $config;
