<?php
/**
 * Copy of syntax_plugin_wrap_closesection
 * Used for special handling of headers
 */
class syntax_plugin_ifauthex_closesection extends DokuWiki_Syntax_Plugin
{
    function getType() { return 'substition';}
    function getPType() { return 'block';}
    function getSort() { return 195; }

    /**
     * Dummy handler
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
