<?php
/**
 * DokuWiki Plugin ifauthex (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Pietro Saccardi <lizardm4@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

require_once(__DIR__ . '/lib/grammar.php');

class syntax_plugin_ifauthex extends DokuWiki_Syntax_Plugin
{
    /** @inheritDoc */
    public function getType()
    {
        return 'formatting';
    }

    /** @inheritDoc */
    function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    /** @inheritDoc */
    function getPType()
    {
        return 'stack';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 158;
    }

    /** @inheritDoc */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<ifauth\b.*?>(?=.*?</ifauth>)', $mode, 'plugin_ifauthex');
    }

    /** @inheritDoc */
    public function postConnect()
    {
        $this->Lexer->addExitPattern('</ifauth>', 'plugin_ifauthex');
        $this->Lexer->addPattern('[ \t]*={2,}[^\n]+={2,}[ \t]*(?=\n)', 'plugin_ifauthex');
    }

    /** @inheritDoc */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        global $conf;
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $matches = null;
                preg_match('/^<ifauth\b(.*?)>$/', $match, $matches);
                if (is_array($matches) && count($matches) > 0) {
                    // The last group contains the expression.
                    // Can't already pre-parse because DokuWiki serializes the
                    // objects that are returned, but it doesn't know about our
                    // custom classes at this point.
                    return array($state, $matches[count($matches) - 1]);
                }
                return array($state, null);
            case DOKU_LEXER_MATCHED:
                // source of the following solution: plugin wrap
                // we have a == header ==, use the core header() renderer
                // (copied from core header() in inc/parser/handler.php)
                $title = trim($match);
                $level = 7 - strspn($title,'=');
                if($level < 1) $level = 1;
                $title = trim($title,'=');
                $title = trim($title);

                $handler->_addCall('header',array($title,$level,$pos), $pos);
                // close the section edit the header could open
#                if ($title && $level <= $conf['maxseclevel']) {
#                    $handler->addPluginCall('ifauthex_closesection', array(), DOKU_LEXER_SPECIAL, $pos, '');
#                }
                break;
            case DOKU_LEXER_UNMATCHED:
                return array($state, $match);
            case DOKU_LEXER_EXIT:
                return array($state, null);
        }
        return false;
    }

    /** @inheritDoc */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        list($state, $exprOrMatch) = $data;

        // never cache
        $renderer->nocache();

        switch ($state) {
            case DOKU_LEXER_ENTER:
                if ($exprOrMatch === null) {
                    // something went wrong
                    return false;
                }
                try {
                    // check if current user should see the content
                    $exprOrMatch = auth_expr_parse($exprOrMatch);
                    $shouldRender = (bool) $exprOrMatch->evaluate();
                    if(!$shouldRender) {
                        // point the renderer's doc to something else, remembering the old one
                        $renderer->meta['ifauthex.originalDoc'] = &$renderer->doc;
                        $ignoredDoc = '';
                        $renderer->doc = &$ignoredDoc;
                        $renderer->meta['ifauthex.isDiverted'] = true;
                    }
                } catch (Exception $e) {
                    // something went wrong parsing the expression
                    msg(hsc($e->getMessage()), -1);
                    return false;
                }
                break;
            case DOKU_LEXER_UNMATCHED:
                $renderer->cdata($exprOrMatch);
                break;
            case DOKU_LEXER_EXIT:
                // point the renderer's doc back to the original
                if($renderer->meta['ifauthex.isDiverted']) {
                    $renderer->doc = &$renderer->meta['ifauthex.originalDoc'];
                    $renderer->meta['ifauthex.isDiverted'] = false;
                }
                break;
        }

        return true;
    }
}

