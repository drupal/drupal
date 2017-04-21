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
 * "name", "description" and "core are required fields in Drupal info files. Also
 * checks the "php" minimum requirement for Drupal 7.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_InfoFiles_RequiredSniff implements PHP_CodeSniffer_Sniff
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

        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -4));
        if ($fileExtension !== 'info') {
            return $end;
        }

        $tokens = $phpcsFile->getTokens();

        $contents = file_get_contents($phpcsFile->getFilename());
        $info     = Drupal_Sniffs_InfoFiles_ClassFilesSniff::drupalParseInfoFormat($contents);
        if (isset($info['name']) === false) {
            $error = '"name" property is missing in the info file';
            $phpcsFile->addError($error, $stackPtr, 'Name');
        }

        if (isset($info['description']) === false) {
            $error = '"description" property is missing in the info file';
            $phpcsFile->addError($error, $stackPtr, 'Description');
        }

        if (isset($info['core']) === false) {
            $error = '"core" property is missing in the info file';
            $phpcsFile->addError($error, $stackPtr, 'Core');
        } else if ($info['core'] === '7.x' && isset($info['php']) === true
            && $info['php'] <= '5.2'
        ) {
            $error = 'Drupal 7 core already requires PHP 5.2';
            $ptr   = Drupal_Sniffs_InfoFiles_ClassFilesSniff::getPtr('php', $info['php'], $phpcsFile);
            $phpcsFile->addError($error, $ptr, 'D7PHPVersion');
        }

        return $end;

    }//end process()


}//end class
