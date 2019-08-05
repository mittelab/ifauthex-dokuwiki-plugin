<?php
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * Copy of syntax_plugin_wrap_closesection
 * Used for special handling of headers
 */
class syntax_plugin_ifauthex_closesection extends DokuWiki_Syntax_Plugin {

    function getType() { return 'substition';}
    function getPType() { return 'block';}
    function getSort() { return 195; }

    /**
     * Dummy handler, this syntax part has no syntax but is directly added to the instructions by the div syntax
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {}

    /**
     * Create output
     */
    function render($mode, Doku_Renderer $renderer, $indata)
    {
        if($mode == 'xhtml'){
            /** @var Doku_Renderer_xhtml $renderer */
            $renderer->finishSectionEdit();
            return true;
        }
        return false;
    }
}
