<?php
/**
 * Drupal_Sniffs_Semantics_FunctionAliasSniff
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Checks that no PHP function name aliases are used.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_Semantics_FunctionAliasSniff extends Drupal_Sniffs_Semantics_FunctionCall
{

    /**
     * Holds all PHP funtion name aliases (keys) and orginals (values). See
     * http://php.net/manual/en/aliases.php
     *
     * @var array
     */
    protected $aliases = array(
                          '_'                          => 'gettext',
                          'add'                        => 'swfmovie_add',
                          'add'                        => 'swfsprite_add',
                          'add_root'                   => 'domxml_add_root',
                          'addaction'                  => 'swfbutton_addAction',
                          'addcolor'                   => 'swfdisplayitem_addColor',
                          'addentry'                   => 'swfgradient_addEntry',
                          'addfill'                    => 'swfshape_addfill',
                          'addshape'                   => 'swfbutton_addShape',
                          'addstring'                  => 'swftext_addString',
                          'addstring'                  => 'swftextfield_addString',
                          'align'                      => 'swftextfield_align',
                          'attributes'                 => 'domxml_attributes',
                          'children'                   => 'domxml_children',
                          'chop'                       => 'rtrim',
                          'close'                      => 'closedir',
                          'com_get'                    => 'com_propget',
                          'com_propset'                => 'com_propput',
                          'com_set'                    => 'com_propput',
                          'die'                        => 'exit',
                          'dir'                        => 'getdir',
                          'diskfreespace'              => 'disk_free_space',
                          'domxml_getattr'             => 'domxml_get_attribute',
                          'domxml_setattr'             => 'domxml_set_attribute',
                          'doubleval'                  => 'floatval',
                          'drawarc'                    => 'swfshape_drawarc',
                          'drawcircle'                 => 'swfshape_drawcircle',
                          'drawcubic'                  => 'swfshape_drawcubic',
                          'drawcubicto'                => 'swfshape_drawcubicto',
                          'drawcurve'                  => 'swfshape_drawcurve',
                          'drawcurveto'                => 'swfshape_drawcurveto',
                          'drawglyph'                  => 'swfshape_drawglyph',
                          'drawline'                   => 'swfshape_drawline',
                          'drawlineto'                 => 'swfshape_drawlineto',
                          'dtd'                        => 'domxml_intdtd',
                          'dumpmem'                    => 'domxml_dumpmem',
                          'fbsql'                      => 'fbsql_db_query',
                          'fputs'                      => 'fwrite',
                          'get_attribute'              => 'domxml_get_attribute',
                          'getascent'                  => 'swffont_getAscent',
                          'getascent'                  => 'swftext_getAscent',
                          'getattr'                    => 'domxml_get_attribute',
                          'getdescent'                 => 'swffont_getDescent',
                          'getdescent'                 => 'swftext_getDescent',
                          'getheight'                  => 'swfbitmap_getHeight',
                          'getleading'                 => 'swffont_getLeading',
                          'getleading'                 => 'swftext_getLeading',
                          'getshape1'                  => 'swfmorph_getShape1',
                          'getshape2'                  => 'swfmorph_getShape2',
                          'getwidth'                   => 'swfbitmap_getWidth',
                          'getwidth'                   => 'swffont_getWidth',
                          'getwidth'                   => 'swftext_getWidth',
                          'gzputs'                     => 'gzwrite',
                          'i18n_convert'               => 'mb_convert_encoding',
                          'i18n_discover_encoding'     => 'mb_detect_encoding',
                          'i18n_http_input'            => 'mb_http_input',
                          'i18n_http_output'           => 'mb_http_output',
                          'i18n_internal_encoding'     => 'mb_internal_encoding',
                          'i18n_ja_jp_hantozen'        => 'mb_convert_kana',
                          'i18n_mime_header_decode'    => 'mb_decode_mimeheader',
                          'i18n_mime_header_encode'    => 'mb_encode_mimeheader',
                          'imap_create'                => 'imap_createmailbox',
                          'imap_fetchtext'             => 'imap_body',
                          'imap_getmailboxes'          => 'imap_list_full',
                          'imap_getsubscribed'         => 'imap_lsub_full',
                          'imap_header'                => 'imap_headerinfo',
                          'imap_listmailbox'           => 'imap_list',
                          'imap_listsubscribed'        => 'imap_lsub',
                          'imap_rename'                => 'imap_renamemailbox',
                          'imap_scan'                  => 'imap_listscan',
                          'imap_scanmailbox'           => 'imap_listscan',
                          'ini_alter'                  => 'ini_set',
                          'is_double'                  => 'is_float',
                          'is_integer'                 => 'is_int',
                          'is_long'                    => 'is_int',
                          'is_real'                    => 'is_float',
                          'is_writeable'               => 'is_writable',
                          'join'                       => 'implode',
                          'key_exists'                 => 'array_key_exists',
                          'labelframe'                 => 'swfmovie_labelFrame',
                          'labelframe'                 => 'swfsprite_labelFrame',
                          'last_child'                 => 'domxml_last_child',
                          'lastchild'                  => 'domxml_last_child',
                          'ldap_close'                 => 'ldap_unbind',
                          'magic_quotes_runtime'       => 'set_magic_quotes_runtime',
                          'mbstrcut'                   => 'mb_strcut',
                          'mbstrlen'                   => 'mb_strlen',
                          'mbstrpos'                   => 'mb_strpos',
                          'mbstrrpos'                  => 'mb_strrpos',
                          'mbsubstr'                   => 'mb_substr',
                          'ming_setcubicthreshold'     => 'ming_setCubicThreshold',
                          'ming_setscale'              => 'ming_setScale',
                          'move'                       => 'swfdisplayitem_move',
                          'movepen'                    => 'swfshape_movepen',
                          'movepento'                  => 'swfshape_movepento',
                          'moveto'                     => 'swfdisplayitem_moveTo',
                          'moveto'                     => 'swffill_moveTo',
                          'moveto'                     => 'swftext_moveTo',
                          'msql'                       => 'msql_db_query',
                          'msql_createdb'              => 'msql_create_db',
                          'msql_dbname'                => 'msql_result',
                          'msql_dropdb'                => 'msql_drop_db',
                          'msql_fieldflags'            => 'msql_field_flags',
                          'msql_fieldlen'              => 'msql_field_len',
                          'msql_fieldname'             => 'msql_field_name',
                          'msql_fieldtable'            => 'msql_field_table',
                          'msql_fieldtype'             => 'msql_field_type',
                          'msql_freeresult'            => 'msql_free_result',
                          'msql_listdbs'               => 'msql_list_dbs',
                          'msql_listfields'            => 'msql_list_fields',
                          'msql_listtables'            => 'msql_list_tables',
                          'msql_numfields'             => 'msql_num_fields',
                          'msql_numrows'               => 'msql_num_rows',
                          'msql_regcase'               => 'sql_regcase',
                          'msql_selectdb'              => 'msql_select_db',
                          'msql_tablename'             => 'msql_result',
                          'mssql_affected_rows'        => 'sybase_affected_rows',
                          'mssql_affected_rows'        => 'sybase_affected_rows',
                          'mssql_close'                => 'sybase_close',
                          'mssql_close'                => 'sybase_close',
                          'mssql_connect'              => 'sybase_connect',
                          'mssql_connect'              => 'sybase_connect',
                          'mssql_data_seek'            => 'sybase_data_seek',
                          'mssql_data_seek'            => 'sybase_data_seek',
                          'mssql_fetch_array'          => 'sybase_fetch_array',
                          'mssql_fetch_array'          => 'sybase_fetch_array',
                          'mssql_fetch_field'          => 'sybase_fetch_field',
                          'mssql_fetch_field'          => 'sybase_fetch_field',
                          'mssql_fetch_object'         => 'sybase_fetch_object',
                          'mssql_fetch_object'         => 'sybase_fetch_object',
                          'mssql_fetch_row'            => 'sybase_fetch_row',
                          'mssql_fetch_row'            => 'sybase_fetch_row',
                          'mssql_field_seek'           => 'sybase_field_seek',
                          'mssql_field_seek'           => 'sybase_field_seek',
                          'mssql_free_result'          => 'sybase_free_result',
                          'mssql_free_result'          => 'sybase_free_result',
                          'mssql_get_last_message'     => 'sybase_get_last_message',
                          'mssql_get_last_message'     => 'sybase_get_last_message',
                          'mssql_min_client_severity'  => 'sybase_min_client_severity',
                          'mssql_min_error_severity'   => 'sybase_min_error_severity',
                          'mssql_min_message_severity' => 'sybase_min_message_severity',
                          'mssql_min_server_severity'  => 'sybase_min_server_severity',
                          'mssql_num_fields'           => 'sybase_num_fields',
                          'mssql_num_fields'           => 'sybase_num_fields',
                          'mssql_num_rows'             => 'sybase_num_rows',
                          'mssql_num_rows'             => 'sybase_num_rows',
                          'mssql_pconnect'             => 'sybase_pconnect',
                          'mssql_pconnect'             => 'sybase_pconnect',
                          'mssql_query'                => 'sybase_query',
                          'mssql_query'                => 'sybase_query',
                          'mssql_result'               => 'sybase_result',
                          'mssql_result'               => 'sybase_result',
                          'mssql_select_db'            => 'sybase_select_db',
                          'mssql_select_db'            => 'sybase_select_db',
                          'multcolor'                  => 'swfdisplayitem_multColor',
                          'mysql'                      => 'mysql_db_query',
                          'mysql_createdb'             => 'mysql_create_db',
                          'mysql_db_name'              => 'mysql_result',
                          'mysql_dbname'               => 'mysql_result',
                          'mysql_dropdb'               => 'mysql_drop_db',
                          'mysql_fieldflags'           => 'mysql_field_flags',
                          'mysql_fieldlen'             => 'mysql_field_len',
                          'mysql_fieldname'            => 'mysql_field_name',
                          'mysql_fieldtable'           => 'mysql_field_table',
                          'mysql_fieldtype'            => 'mysql_field_type',
                          'mysql_freeresult'           => 'mysql_free_result',
                          'mysql_listdbs'              => 'mysql_list_dbs',
                          'mysql_listfields'           => 'mysql_list_fields',
                          'mysql_listtables'           => 'mysql_list_tables',
                          'mysql_numfields'            => 'mysql_num_fields',
                          'mysql_numrows'              => 'mysql_num_rows',
                          'mysql_selectdb'             => 'mysql_select_db',
                          'mysql_tablename'            => 'mysql_result',
                          'name'                       => 'domxml_attrname',
                          'new_child'                  => 'domxml_new_child',
                          'new_xmldoc'                 => 'domxml_new_xmldoc',
                          'nextframe'                  => 'swfmovie_nextFrame',
                          'nextframe'                  => 'swfsprite_nextFrame',
                          'node'                       => 'domxml_node',
                          'oci8append'                 => 'ocicollappend',
                          'oci8assign'                 => 'ocicollassign',
                          'oci8assignelem'             => 'ocicollassignelem',
                          'oci8close'                  => 'ocicloselob',
                          'oci8free'                   => 'ocifreecoll',
                          'oci8free'                   => 'ocifreedesc',
                          'oci8getelem'                => 'ocicollgetelem',
                          'oci8load'                   => 'ociloadlob',
                          'oci8max'                    => 'ocicollmax',
                          'oci8ocifreecursor'          => 'ocifreestatement',
                          'oci8save'                   => 'ocisavelob',
                          'oci8savefile'               => 'ocisavelobfile',
                          'oci8size'                   => 'ocicollsize',
                          'oci8trim'                   => 'ocicolltrim',
                          'oci8writetemporary'         => 'ociwritetemporarylob',
                          'oci8writetofile'            => 'ociwritelobtofile',
                          'odbc_do'                    => 'odbc_exec',
                          'odbc_field_precision'       => 'odbc_field_len',
                          'output'                     => 'swfmovie_output',
                          'parent'                     => 'domxml_parent',
                          'pdf_add_outline'            => 'pdf_add_bookmark',
                          'pg_clientencoding'          => 'pg_client_encoding',
                          'pg_setclientencoding'       => 'pg_set_client_encoding',
                          'pos'                        => 'current',
                          'recode'                     => 'recode_string',
                          'remove'                     => 'swfmovie_remove',
                          'remove'                     => 'swfsprite_remove',
                          'root'                       => 'domxml_root',
                          'rotate'                     => 'swfdisplayitem_rotate',
                          'rotateto'                   => 'swfdisplayitem_rotateTo',
                          'rotateto'                   => 'swffill_rotateTo',
                          'save'                       => 'swfmovie_save',
                          'savetofile'                 => 'swfmovie_saveToFile',
                          'scale'                      => 'swfdisplayitem_scale',
                          'scaleto'                    => 'swfdisplayitem_scaleTo',
                          'scaleto'                    => 'swffill_scaleTo',
                          'set_attribute'              => 'domxml_set_attribute',
                          'set_content'                => 'domxml_set_content',
                          'setaction'                  => 'swfbutton_setAction',
                          'setattr'                    => 'domxml_set_attribute',
                          'setbackground'              => 'swfmovie_setBackground',
                          'setbounds'                  => 'swftextfield_setBounds',
                          'setcolor'                   => 'swftext_setColor',
                          'setcolor'                   => 'swftextfield_setColor',
                          'setdepth'                   => 'swfdisplayitem_setDepth',
                          'setdimension'               => 'swfmovie_setDimension',
                          'setdown'                    => 'swfbutton_setDown',
                          'setfont'                    => 'swftext_setFont',
                          'setfont'                    => 'swftextfield_setFont',
                          'setframes'                  => 'swfmovie_setFrames',
                          'setframes'                  => 'swfsprite_setFrames',
                          'setheight'                  => 'swftext_setHeight',
                          'setheight'                  => 'swftextfield_setHeight',
                          'sethit'                     => 'swfbutton_setHit',
                          'setindentation'             => 'swftextfield_setIndentation',
                          'setleftfill'                => 'swfshape_setleftfill',
                          'setleftmargin'              => 'swftextfield_setLeftMargin',
                          'setline'                    => 'swfshape_setline',
                          'setlinespacing'             => 'swftextfield_setLineSpacing',
                          'setmargins'                 => 'swftextfield_setMargins',
                          'setmatrix'                  => 'swfdisplayitem_setMatrix',
                          'setname'                    => 'swfdisplayitem_setName',
                          'setname'                    => 'swftextfield_setName',
                          'setover'                    => 'swfbutton_setOver',
                          'setrate'                    => 'swfmovie_setRate',
                          'setratio'                   => 'swfdisplayitem_setRatio',
                          'setrightfill'               => 'swfshape_setrightfill',
                          'setrightmargin'             => 'swftextfield_setRightMargin',
                          'setspacing'                 => 'swftext_setSpacing',
                          'setup'                      => 'swfbutton_setUp',
                          'show_source'                => 'highlight_file',
                          'sizeof'                     => 'count',
                          'skewx'                      => 'swfdisplayitem_skewX',
                          'skewxto'                    => 'swfdisplayitem_skewXTo',
                          'skewxto'                    => 'swffill_skewXTo',
                          'skewy'                      => 'swfdisplayitem_skewY',
                          'skewyto'                    => 'swfdisplayitem_skewYTo',
                          'skewyto'                    => 'swffill_skewYTo',
                          'snmpwalkoid'                => 'snmprealwalk',
                          'strchr'                     => 'strstr',
                          'streammp3'                  => 'swfmovie_streamMp3',
                          'swfaction'                  => 'swfaction_init',
                          'swfbitmap'                  => 'swfbitmap_init',
                          'swfbutton'                  => 'swfbutton_init',
                          'swffill'                    => 'swffill_init',
                          'swffont'                    => 'swffont_init',
                          'swfgradient'                => 'swfgradient_init',
                          'swfmorph'                   => 'swfmorph_init',
                          'swfmovie'                   => 'swfmovie_init',
                          'swfshape'                   => 'swfshape_init',
                          'swfsprite'                  => 'swfsprite_init',
                          'swftext'                    => 'swftext_init',
                          'swftextfield'               => 'swftextfield_init',
                          'xptr_new_context'           => 'xpath_new_context',
                         );


    /**
     * Returns an array of function names this test wants to listen for.
     *
     * @return array
     */
    public function registerFunctionNames()
    {
        return array_keys($this->aliases);

    }//end registerFunctionNames()


    /**
     * Processes this function call.
     *
     * @param PHP_CodeSniffer_File $phpcsFile
     *   The file being scanned.
     * @param int                  $stackPtr
     *   The position of the function call in the stack.
     * @param int                  $openBracket
     *   The position of the opening parenthesis in the stack.
     * @param int                  $closeBracket
     *   The position of the closing parenthesis in the stack.
     *
     * @return void
     */
    public function processFunctionCall(
        PHP_CodeSniffer_File $phpcsFile,
        $stackPtr,
        $openBracket,
        $closeBracket
    ) {
        $tokens = $phpcsFile->getTokens();
        $error  = '%s() is a function name alias, use %s() instead';
        $name   = $tokens[$stackPtr]['content'];
        $data   = array(
                   $name,
                   $this->aliases[$name],
                  );
        $phpcsFile->addError($error, $stackPtr, 'FunctionAlias', $data);

    }//end processFunctionCall()


}//end class
