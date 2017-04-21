<?php
/**
 * Unit test class for all bad files.
 */

/**
 * Unit test class for all bad files.
 */
class Drupal_BadUnitTest extends CoderSniffUnitTest
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
            case 'bad.css':
                return array(
                        1 => 1,
                        2 => 1,
                        3 => 2,
                        4 => 1,
                        5 => 1,
                        6 => 2,
                        7 => 1,
                        8 => 1,
                        9 => 1,
                        12 => 3,
                        16 => 1,
                        17 => 1,
                        21 => 1,
                        26 => 1,
                        27 => 1,
                        31 => 1,
                        36 => 1,
                       );
            case 'bad.info':
                return array(
                        1 => 3,
                        4 => 1,
                        6 => 1,
                       );
            case 'bad.install':
                return array(
                        13 => 1,
                        16 => 1,
                        51 => 1,
                        58 => 1,
                       );
            case 'bad.module':
                return array(
                        12 => 1,
                        19 => 1,
                        26 => 1,
                        33 => 1,
                        44 => 1,
                        45 => 1,
                       );
            case 'bad.php':
                return array(
                        3 => 2,
                        5 => 1,
                        7 => 1,
                        10 => 1,
                        12 => 1,
                        16 => 1,
                        19 => 2,
                        20 => 1,
                        21 => 1,
                        22 => 1,
                        24 => 1,
                        25 => 2,
                        28 => 1,
                        31 => 1,
                        35 => 1,
                        39 => 2,
                        42 => 1,
                        44 => 1,
                        45 => 1,
                        46 => 1,
                        47 => 1,
                        48 => 1,
                        49 => 1,
                        50 => 1,
                        51 => 1,
                        52 => 1,
                        53 => 1,
                        54 => 1,
                        55 => 1,
                        56 => 2,
                        57 => 2,
                        58 => 2,
                        59 => 2,
                        60 => 2,
                        61 => 2,
                        62 => 2,
                        63 => 2,
                        64 => 2,
                        65 => 2,
                        66 => 2,
                        67 => 1,
                        68 => 1,
                        69 => 1,
                        70 => 1,
                        71 => 2,
                        72 => 1,
                        73 => 1,
                        74 => 1,
                        75 => 1,
                        76 => 1,
                        79 => 1,
                        80 => 1,
                        81 => 1,
                        82 => 1,
                        83 => 1,
                        84 => 1,
                        85 => 1,
                        86 => 1,
                        87 => 1,
                        88 => 1,
                        89 => 1,
                        90 => 1,
                        91 => 1,
                        92 => 1,
                        93 => 1,
                        94 => 1,
                        95 => 1,
                        96 => 1,
                        97 => 1,
                        99 => 2,
                        100 => 1,
                        101 => 1,
                        102 => 1,
                        105 => 1,
                        106 => 1,
                        107 => 1,
                        108 => 1,
                        109 => 1,
                        110 => 1,
                        111 => 1,
                        112 => 1,
                        113 => 1,
                        114 => 1,
                        115 => 1,
                        116 => 1,
                        117 => 1,
                        118 => 1,
                        119 => 1,
                        120 => 1,
                        121 => 1,
                        122 => 1,
                        123 => 1,
                        124 => 1,
                        125 => 1,
                        128 => 1,
                        129 => 1,
                        130 => 1,
                        135 => 1,
                        141 => 2,
                        142 => 3,
                        143 => 2,
                        144 => 3,
                        146 => 1,
                        151 => 1,
                        160 => 1,
                        161 => 1,
                        162 => 1,
                        163 => 3,
                        166 => 1,
                        167 => 1,
                        171 => 1,
                        175 => 1,
                        177 => 1,
                        178 => 4,
                        179 => 2,
                        180 => 2,
                        181 => 5,
                        183 => 3,
                        185 => 3,
                        188 => 2,
                        192 => 1,
                        193 => 2,
                        194 => 2,
                        196 => 3,
                        198 => 2,
                        202 => 2,
                        206 => 2,
                        209 => 1,
                        213 => 1,
                        214 => 1,
                        216 => 2,
                        218 => 1,
                        222 => 2,
                        225 => 2,
                        230 => 1,
                        233 => 1,
                        237 => 1,
                        241 => 1,
                        245 => 1,
                        248 => 1,
                        249 => 2,
                        253 => 3,
                        257 => 2,
                        263 => 1,
                        269 => 1,
                        273 => 1,
                        277 => 1,
                        279 => 2,
                        281 => 1,
                        283 => 2,
                        285 => 1,
                        289 => 1,
                        290 => 1,
                        291 => 1,
                        294 => 1,
                        300 => 1,
                        307 => 1,
                        308 => 1,
                        309 => 1,
                        310 => 1,
                        311 => 1,
                        312 => 1,
                        313 => 2,
                        314 => 1,
                        318 => 1,
                        325 => 2,
                        327 => 1,
                        332 => 2,
                        334 => 1,
                        338 => 1,
                        339 => 1,
                        348 => 1,
                        356 => 1,
                        357 => 1,
                        358 => 1,
                        359 => 1,
                        360 => 1,
                        362 => 1,
                        363 => 1,
                        365 => 1,
                        366 => 1,
                        369 => 1,
                        372 => 2,
                        375 => 1,
                        376 => 1,
                        379 => 1,
                        383 => 1,
                        384 => 1,
                        385 => 1,
                        386 => 1,
                        387 => 1,
                        389 => 1,
                        390 => 1,
                        391 => 1,
                        392 => 1,
                        393 => 1,
                        394 => 1,
                        395 => 1,
                        396 => 1,
                        397 => 1,
                        398 => 1,
                        399 => 1,
                        400 => 1,
                        401 => 1,
                        403 => 1,
                        406 => 1,
                        407 => 3,
                        411 => 2,
                        417 => 1,
                        418 => 2,
                        421 => 1,
                        422 => 1,
                        424 => 2,
                        426 => 2,
                        428 => 1,
                        436 => 1,
                        438 => 1,
                        443 => 2,
                        448 => 2,
                        452 => 1,
                        495 => 1,
                        504 => 1,
                        514 => 1,
                        522 => 1,
                        532 => 1,
                        541 => 2,
                        550 => 3,
                        552 => 1,
                        566 => 3,
                        575 => 1,
                        578 => 2,
                        588 => 1,
                        590 => 1,
                        592 => 1,
                        594 => 2,
                        595 => 1,
                        596 => 1,
                        599 => 2,
                        601 => 2,
                        602 => 1,
                        603 => 1,
                        620 => 1,
                        621 => 5,
                        622 => 1,
                        629 => 1,
                        638 => 1,
                        646 => 2,
                        648 => 1,
                        656 => 1,
                        658 => 1,
                        661 => 1,
                        671 => 1,
                        678 => 1,
                        685 => 1,
                        693 => 1,
                        697 => 1,
                        698 => 1,
                        704 => 1,
                        709 => 1,
                        712 => 1,
                        713 => 2,
                        714 => 1,
                        716 => 1,
                        717 => 1,
                        721 => 1,
                        724 => 1,
                        725 => 1,
                        726 => 1,
                        727 => 2,
                        730 => 1,
                        731 => 1,
                        732 => 1,
                        733 => 2,
                        734 => 1,
                        735 => 1,
                        738 => 1,
                        741 => 1,
                        744 => 1,
                        750 => 1,
                        756 => 1,
                        765 => 1,
                        775 => 1,
                        791 => 1,
                        795 => 4,
                        796 => 1,
                        799 => 1,
                        800 => 1,
                        801 => 1,
                        802 => 1,
                        804 => 1,
                        805 => 1,
                        806 => 1,
                        807 => 1,
                        809 => 3,
                        815 => 1,
                        820 => 1,
                        827 => 1,
                        833 => 2,
                       );
        }
        return array();

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
        switch ($testFile) {
            case 'bad.module':
                return array(
                        7 => 1,
                       );
            case 'bad.php':
                return array(
                        14 => 1,
                        139 => 1,
                        151 => 1,
                        156 => 1,
                        193 => 1,
                        202 => 1,
                        360 => 1,
                        363 => 1,
                        366 => 1,
                        382 => 1,
                        433 => 1,
                        434 => 1,
                        436 => 1,
                        440 => 1,
                        460 => 1,
                        467 => 1,
                        474 => 1,
                        485 => 1,
                        495 => 1,
                        787 => 1,
                        788 => 1,
                        809 => 1,
                        823 => 1,
                        824 => 1,
                       );
        }//end switch

        return array();

    }//end getWarningList()


    /**
     * Returns a list of test files that should be checked.
     *
     * @return array The list of test files.
     */
    protected function getTestFiles()
    {
        $dir = dirname(__FILE__);
        $di  = new DirectoryIterator($dir);

        foreach ($di as $file) {
            $path = $file->getPathname();
            if ($path !== __FILE__ && $file->isFile() && preg_match('/\.fixed$/', $path) !== 1) {
                $testFiles[] = $path;
            }
        }

        // Get them in order.
        sort($testFiles);
        return $testFiles;

    }//end getTestFiles()


    /**
     * Returns a list of sniff codes that should be checked in this test.
     *
     * @return array The list of sniff codes.
     */
    protected function getSniffCodes()
    {
        // We want to test all sniffs defined in the standard.
        return array();

    }//end getSniffCodes()


}//end class

?>
