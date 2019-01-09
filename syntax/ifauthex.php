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

require_once('grammar.php');

class syntax_plugin_ifauthex extends DokuWiki_Syntax_Plugin
{
    private $_authExpression = null;
    /**
     * @return string Syntax mode type
     */
    public function getType()
    {
        return 'substition';
    }

    /**
     * @return string Paragraph type
     */
    public function getPType()
    {
        return 'normal';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort()
    {
        return 350;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<ifauth(ex)?\s+.*?>(?=.*?\x3C/ifauth(ex)?\x3E)', $mode, 'plugin_ifauthex_ifauthex');
    }

   public function postConnect()
   {
       $this->Lexer->addExitPattern('<\/ifauth(ex)?>', 'plugin_ifauthex_ifauthex');
   }

    /**
     * Handle matches of the ifauthex syntax
     *
     * @param string       $match   The match of the syntax
     * @param int          $state   The state of the handler
     * @param int          $pos     The position in the document
     * @param Doku_Handler $handler The handler
     *
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $matches = null;
                preg_match('^<ifauth(ex)?\s+(.*?)>$', $match);
                $authExpr = $matches[count($matches) - 1]; // the last group
                return array($state, $authExpr);
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

    /**
     * Render xhtml output or metadata
     *
     * @param string        $mode     Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array         $data     The data from the handler() function
     *
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode !== 'xhtml') {
            list($state, $expr) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    // Parse and store the expression
                    try {
                        $this->_authExpression = parse($expr);
                    } catch (Exception $e) {
                        $this->_authExpression = null;
                        return false;
                    }
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $render = false;
                    if ($this->_authExpression !== null) {
                        try {
                            $render = $this->_authExpression->evaluate();
                        } catch (Exception $e) {
                            return false;
                        } finally {
                            $this->_authExpression = null;
                        }
                    }
                    if ($render) {
                        $output = p_render('xhtml', p_get_instructions($match), $info);
                        // Remove '\n<p>\n' from start and '\n</p>\n' from the end.
                        preg_match('/^\s*<p>\s*/i', $output, $match);
                        if (count($match) > 0) {
                            $output = substr($output, strlen($match[0]));
                        }
                        preg_match('/\s*<\/p>\s*$/i', $output, $match);
                        if (count($match) > 0) {
                            $output = substr($output, -strlen($match[0]));
                        }
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

