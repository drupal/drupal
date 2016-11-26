<?php

class Drupal_Sniffs_Commenting_InlineCommentUnitTest extends CoderSniffUnitTest
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
        return array(
                8 => 1,
                10 => 1,
                13 => 1,
                15 => 1,
                20 => 1,
                24 => 1,
                44 => 1,
                47 => 1,
                59 => 2,
               );

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
        return array(
                16 => 1,
               );

    }//end getWarningList()


}//end class
