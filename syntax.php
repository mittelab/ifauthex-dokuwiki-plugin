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
    private $_doRender = null;

    public function getType() { return 'formatting'; }

    public function getSort() { return 350; }

    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('<ifauth\b.*?>(?=.*?</ifauth>)', $mode, 'plugin_ifauthex');
    }

    public function postConnect() {
       $this->Lexer->addExitPattern('</ifauth>', 'plugin_ifauthex');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler) {
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
                break;
            case DOKU_LEXER_UNMATCHED:
                return array($state, $match);
                break;
            case DOKU_LEXER_EXIT:
                return array($state, null);
                break;
        }
        return array();
    }

    public function render($mode, Doku_Renderer $renderer, $data) {
        if ($mode == 'xhtml') {
            list($state, $exprOrMatch) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    if ($exprOrMatch === null) {
                        return false;
                    }
                    try {
                        $exprOrMatch = auth_expr_parse($exprOrMatch);
                        $this->_doRender = $exprOrMatch->evaluate();
                    } catch (Exception $e) {
                        $this->_doRender = null;
                        return false;
                    }
                    break;
                case DOKU_LEXER_UNMATCHED:
                    if ($this->_doRender === true) {
                        $output = p_render('xhtml', p_get_instructions($exprOrMatch), $info);
                        // Remove '\n<p>\n' from start and '\n</p>\n' from the end.
                        $output = preg_replace('/^\s*<p>\s*/i', '', $output);
                        $output = preg_replace('/\s*<\/p>\s*$/i', '', $output);
                        $renderer->doc .= $output;
                    }
                    $renderer->nocache();
                    break;
                case DOKU_LEXER_EXIT:
                    break;
            }
        }
        return true;
    }
}

