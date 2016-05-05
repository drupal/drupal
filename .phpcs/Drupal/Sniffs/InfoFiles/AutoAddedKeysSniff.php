<?php
/**
 * Drupal_Sniffs_InfoFiles_RequiredSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * "version", "project" and "timestamp" are added automatically by drupal.org
 * packaging scripts.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_InfoFiles_AutoAddedKeysSniff implements PHP_CodeSniffer_Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_INLINE_HTML);

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // Only run this sniff once per info file.
        $end = (count($phpcsFile->getTokens()) + 1);

        if (preg_match('/\.info$/', $phpcsFile->getFilename()) === 1) {
            // Drupal 7 style info file.
            $contents = file_get_contents($phpcsFile->getFilename());
            $info     = Drupal_Sniffs_InfoFiles_ClassFilesSniff::drupalParseInfoFormat($contents);
        } else if (preg_match('/\.info\.yml$/', $phpcsFile->getFilename()) === 1) {
            // Drupal 8 style info.yml file.
            $contents = file_get_contents($phpcsFile->getFilename());
            try {
                $info = \Symfony\Component\Yaml\Yaml::parse($contents);
            } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
                // If the YAML is invalid we ignore this file.
                return $end;
            }
        } else {
            return $end;
        }

        if (isset($info['project']) === true) {
            $warning = 'Remove "project" form the info file, it will be added by drupal.org packaging automatically';
            $phpcsFile->addWarning($warning, $stackPtr, 'Project');
        }

        if (isset($info['timestamp']) === true) {
            $warning = 'Remove "timestamp" form the info file, it will be added by drupal.org packaging automatically';
            $phpcsFile->addWarning($warning, $stackPtr, 'Timestamp');
        }

        // "version" is special: we want to allow it in core, but not anywhere else.
        if (isset($info['version']) === true && strpos($phpcsFile->getFilename(), '/core/') === false) {
            $warning = 'Remove "version" form the info file, it will be added by drupal.org packaging automatically';
            $phpcsFile->addWarning($warning, $stackPtr, 'Version');
        }

        return $end;

    }//end process()


}//end class
