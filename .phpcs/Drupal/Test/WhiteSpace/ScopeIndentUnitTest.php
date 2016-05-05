<?php

class Drupal_Sniffs_WhiteSpace_ScopeIndentUnitTest extends CoderSniffUnitTest
{

    /**
     * Returns the lines where errors should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of errors that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getErrorList($testFile)
    {
        switch ($testFile) {
            case 'ScopeIndentUnitTest.1.js':
                return array(
                        3 => 1,
                        6 => 1,
                        10 => 1,
                        11 => 1,
                        12 => 1,
                        13 => 1,
                        18 => 1,
                        21 => 1,
                        22 => 1,
                        23 => 1,
                        24 => 1,
                        27 => 1,
                       );
            default:
                return array(
                        6 => 1,
                        18 => 1,
                        20 => 1,
                       );
        }

    }//end getErrorList()


    /**
     * Returns the lines where warnings should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of warnings that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getWarningList($testFile)
    {
        return array();

    }//end getWarningList()


}//end class
