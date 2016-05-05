<?php

class Drupal_Sniffs_Commenting_PostStatementCommentUnitTest extends CoderSniffUnitTest
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
            case 'PostStatementCommentUnitTest.inc':
                return array(
                        3 => 1,
                        7 => 1,
                       );
            case 'PostStatementCommentUnitTest.1.inc':
                return array(1 => 1);
            case 'PostStatementCommentUnitTest.2.inc':
                return array(6 => 1);
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
