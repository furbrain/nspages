<?php
/**
 * Plugin nspages : Displays nicely a list of the pages of a namespace
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Guillaume Turri <guillaume.turri@gmail.com>
 * @author  Daniel Schranz <xla@gmx.at>
 * @author  Ignacio Bergmann
 * @author  Andreas Gohr <gohr@cosmocode.de>
 * @author  Ghassem Tofighi <ghassem@gmail.com>
 */

use dokuwiki\Utf8\PhpString;

if(!defined('DOKU_INC')) die();
require_once 'printers/printerLineBreak.php';
require_once 'printers/printerOneLine.php';
require_once 'printers/printerSimpleList.php';
require_once 'printers/printerNice.php';
require_once 'printers/printerPictures.php';
require_once 'printers/printerTree.php';
require_once 'fileHelper/fileHelper.php';
require_once 'optionParser.php';
require_once 'namespaceFinder.php';

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_nspages extends DokuWiki_Syntax_Plugin {
    function connectTo($aMode) {
        $this->Lexer->addSpecialPattern('<nspages[^>]*>', $aMode, 'plugin_nspages');
    }

    function getSort() {
        //Execute before html mode
        return 189;
    }

    function getType() {
        return 'substition';
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {
        $return = $this->_getDefaultOptions();
        $return['pos'] = $pos;

        $match = PhpString::substr($match, 8, -1); //9 = strlen("<nspages")
        $match .= ' ';

        optionParser::checkOption($match, "subns", $return['subns'], true);
        optionParser::checkOption($match, "nopages", $return['nopages'], true);
        optionParser::checkOption($match, "simpleListe?", $return['simpleList'], true);
        optionParser::checkOption($match, "tree", $return['tree'], true);
        optionParser::checkOption($match, "numberedListe?", $return['numberedList'], true);
        optionParser::checkOption($match, "simpleLineBreak", $return['lineBreak'], true);
        optionParser::checkOption($match, "title", $return['title'], true);
        optionParser::checkOption($match, "idAndTitle", $return['idAndTitle'], true);
        optionParser::checkOption($match, "h1", $return['title'], true);
        optionParser::checkOption($match, "simpleLine", $return['simpleLine'], true);
        optionParser::checkOption($match, "sort(By)?Id", $return['sortid'], true);
        optionParser::checkOption($match, "reverse", $return['reverse'], true);
        optionParser::checkOption($match, "pagesinns", $return['pagesinns'], true);
        optionParser::checkOption($match, "nat(ural)?Order", $return['natOrder'], true);
        optionParser::checkOption($match, "sort(By)?Date", $return['sortDate'], true);
        optionParser::checkOption($match, "sort(By)?CreationDate", $return['sortByCreationDate'], true);
        optionParser::checkOption($match, "hidenopages", $return['hidenopages'], true);
        optionParser::checkOption($match, "hidenons", $return['hidenons'], true);
        optionParser::checkOption($match, "hidenosubns", $return['hidenosubns'], true);
        optionParser::checkOption($match, "showhidden", $return['showhidden'], true);
        optionParser::checkOption($match, "(use)?Pictures?", $return['usePictures'], true);
        optionParser::checkOption($match, "(modification)?Dates?OnPictures?", $return['modificationDateOnPictures'], true);
        optionParser::checkOption($match, "displayModificationDates?", $return["displayModificationDate"], true);
        optionParser::checkOption($match, "includeItemsInTOC", $return["includeItemsInTOC"], true);
        optionParser::checkOption($match, "sidebar", $return["sidebar"], true);
        optionParser::checkRecurse($match, $return['maxDepth']);
        optionParser::checkNbColumns($match, $return['nbCol']);
        optionParser::checkSimpleStringArgument($match, $return['textPages'], $this, 'textPages');
        optionParser::checkSimpleStringArgument($match, $return['customTitle'], $this, 'customTitle');
        optionParser::checkSimpleStringArgument($match, $return['sortByMetadata'], $this, 'sortByMetadata');
        optionParser::checkSimpleStringArgument($match, $return['textNS'], $this, 'textNS');
        optionParser::checkDictOrder($match, $return['dictOrder'], $this);
        optionParser::checkRegEx($match, "pregPages?On=\"([^\"]*)\"", $return['pregPagesOn']);
        optionParser::checkRegEx($match, "pregPages?Off=\"([^\"]*)\"", $return['pregPagesOff']);
        optionParser::checkRegEx($match, "pregPages?TitleOn=\"([^\"]*)\"", $return['pregPagesTitleOn']);
        optionParser::checkRegEx($match, "pregPages?TitleOff=\"([^\"]*)\"", $return['pregPagesTitleOff']);
        optionParser::checkRegEx($match, "pregNSOn=\"([^\"]*)\"", $return['pregNSOn']);
        optionParser::checkRegEx($match, "pregNSOff=\"([^\"]*)\"", $return['pregNSOff']);
        optionParser::checkRegEx($match, "pregNSTitleOn=\"([^\"]*)\"", $return['pregNSTitleOn']);
        optionParser::checkRegEx($match, "pregNSTitleOff=\"([^\"]*)\"", $return['pregNSTitleOff']);
        optionParser::checkNbItemsMax($match, $return['nbItemsMax']);
        optionParser::checkGlobalExclude($this->getConf('global_exclude'), $return['excludedPages'], $return['excludedNS']);
        optionParser::checkExclude($match, $return['excludedPages'], $return['excludedNS'], $return['excludeSelfPage']);
        optionParser::checkAnchorName($match, $return['anchorName']);
        optionParser::checkActualTitle($match, $return['actualTitleLevel']);
        optionParser::checkSimpleStringArgument($match, $return['defaultPicture'], $this, 'defaultPicture');

        //Now, only the wanted namespace remains in $match
        $match = strtolower(trim($match));
        if ($return["sidebar"]) {
            // Don't bother resolving or sanitizing now: it will be done at render-time in this mode
            $return['wantedNS'] = $match;
        } else {
            $nsFinder = new namespaceFinder($match);
            $return['wantedNS'] = $nsFinder->getWantedNs();
            $return['safe'] = $nsFinder->isNsSafe();
            $return['wantedDir'] = $nsFinder->getWantedDirectory();
        }

        return $return;
    }

    private function _getDefaultOptions(){
        return array(
            'subns'         => false, 'nopages' => false, 'simpleList' => false, 'lineBreak' => false,
            'excludedPages' => array(), 'excludedNS' => array(), 'excludeSelfPage' => false,
            'title'         => false, 'wantedNS' => '', 'wantedDir' => '', 'safe' => true,
            'textNS'        => '', 'textPages' => '', 'pregPagesOn' => array(),
            'customTitle'   => null,
            'pregPagesOff'  => array(), 'pregNSOn' => array(), 'pregNSOff' => array(),
            'pregPagesTitleOn' => array(), 'pregPagesTitleOff' => array(),
            'pregNSTitleOn' => array(), 'pregNSTitleOff' => array(),
            'maxDepth'      => (int) 1, 'nbCol' => 3, 'simpleLine' => false,
            'sortid'        => false, 'reverse' => false,
            'pagesinns'     => false, 'anchorName' => null, 'actualTitleLevel' => false,
            'idAndTitle'    => false, 'nbItemsMax' => 0, 'numberedList' => false,
            'natOrder'      => false, 'sortDate' => false,
            'hidenopages'   => false, 'hidenons' => false, 'hidenosubns' => false, 'usePictures' => false,
            'showhidden'    => false, 'dictOrder' => false,
            'modificationDateOnPictures' => false,
            'displayModificationDate' => false,
            'sortByCreationDate' => false, 'defaultPicture' => null, 'tree' => false,
            'includeItemsInTOC' => false, 'sidebar' => false,
        );
    }

    function render($mode, Doku_Renderer $renderer, $data) {
        $this->_deactivateTheCacheIfNeeded($renderer);

        if ($data['modificationDateOnPictures']){
            action_plugin_nspages::logUseLegacySyntax();
        }

        //Load lang now rather than at handle-time, otherwise it doesn't
        //behave well with the translation plugin (it seems like we cache strings
        //even if the lang doesn't match)
        $this->_denullifyLangOptions($data);
        $this->_denullifyPictureOptions($data);
        $printer = $this->_selectPrinter($mode, $renderer, $data);

        if ($data['sidebar']) {
            if ($data['wantedNS'] !== '') {
                $printer->printErrorSidebarDoestAcceptNamespace($data['wantedNS']);
                return TRUE;
            }
            if ($mode === "metadata") {
                // In this case $INFO is null so there is not much we can do,
                // but anyway in "sidebar" mode we're not really going to generate metadata of the current page
                // so it rather makes sense to exiting without printing anything.
                return TRUE;
            }
            global $INFO;
            $data['wantedNS'] = $INFO['namespace'];
            $data['safe'] = true;
            $data['wantedDir'] = namespaceFinder::namespaceToDirectory($data['wantedNS']);
        }


        if( ! $this->_isNamespaceUsable($data))
        {
            if( ! $data['hidenons']) {
                $printer->printUnusableNamespace($data['wantedNS']);
            }
            return TRUE;
        }

        $fileHelper = new fileHelper($data, $this->getConf('custom_title_allow_list_metadata'));
        $pages = $fileHelper->getPages();
        $subnamespaces = $fileHelper->getSubnamespaces();
        if ( $this->_shouldPrintPagesAmongNamespaces($data) ){
            $subnamespaces = array_merge($subnamespaces, $pages);
        }

        $printer->printBeginning();
        $this->_print($printer, $data, $subnamespaces, $pages);
        $printer->printEnd();
        return TRUE;
    }

    function _denullifyLangOptions(&$data){
        if ( is_null($data['textNS']) ){
            $data['textNS'] = $this->getLang('subcats');
        }

        if ( is_null($data['textPages']) ){
            $data['textPages'] = $this->getLang('pagesinthiscat');
        }
    }

    function _denullifyPictureOptions(&$data){
        if ( is_null($data['defaultPicture']) ){
            $data['defaultPicture'] = $this->getConf('default_picture');
        }
    }

    private function _shouldPrintPagesAmongNamespaces($data){
        return $data['pagesinns'];
    }

    private function _print($printer, $data, $subnamespaces, $pages){
        if($data['subns']) {
            $printer->printTOC($subnamespaces, 'subns', $data['textNS'], $data['hidenosubns']);
        }

        if(!$this->_shouldPrintPagesAmongNamespaces($data)) {

            if ( $this->_shouldPrintTransition($data) ){
              $printer->printTransition();
            }

            if(!$data['nopages']) {
                $printer->printTOC($pages, 'page', $data['textPages'], $data['hidenopages']);
            }
        }
    }

    private function _shouldPrintTransition($data){
        return $data['textPages'] === '' && !$data['nopages'] && $data['subns'];
    }

    private function _isNamespaceUsable($data){
        global $conf;
        return @opendir($conf['datadir'] . '/' . $data['wantedDir']) !== false && $data['safe'];
    }

    private function _selectPrinter($mode, &$renderer, $data){
        if($data['simpleList']) {
            return new nspages_printerSimpleList($this, $mode, $renderer, $data);
        } else if($data['numberedList']){
            return new nspages_printerSimpleList($this, $mode, $renderer, $data, true);
        } else if($data['simpleLine']) {
            return new nspages_printerOneLine($this, $mode, $renderer, $data);
        } else if ($data['lineBreak']){
            return new nspages_printerLineBreak($this, $mode, $renderer, $data);
        } else if ($data['usePictures'] && $mode == 'xhtml') { //This printer doesn't support non html mode yet
            return new nspages_printerPictures($this, $mode, $renderer, $data);
        } else if ($data['tree']) {
            return new nspages_printerTree($this, $mode, $renderer, $data);
        } else if($mode == 'xhtml') {
            return new nspages_printerNice($this, $mode, $renderer, $data['nbCol'], $data['anchorName'], $data);
        }
        return new nspages_printerSimpleList($this, $mode, $renderer, $data);
    }

    private function _deactivateTheCacheIfNeeded(&$renderer) {
        if ($this->getConf('cache') == 1){
            $renderer->nocache(); //disable cache
        }
    }
}
