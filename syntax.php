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
    private $_nestedRenderPermission = [true];

    /**
     * Returns a boolean representing whether at this depth level rendering is allowed.
     */
    private function innermostShouldRender()
    {
        return end($this->_nestedRenderPermission);
    }

    /**
     * Pushes the permission to render of the current tag into the stack.
     * It might not change the value of @ref innermostShouldRender, if rendering was
     * already previously disabled by an outer tag.
     */
    private function pushInnerPermission($perm) {
        array_push($this->_nestedRenderPermission, $perm && $this->innermostShouldRender());
    }

    /**
     * Returns a boolean representing whether the content at the current nesting level is being rendered.
     * It then pops this boolean from the stack.
     */
    private function popShouldRender() {
        return array_pop($this->_nestedRenderPermission);
    }

    /** @inheritDoc */
    public function getType()
    {
        return 'formatting';
    }

    /** @inheritDoc */
    function getAllowedTypes()
    {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs', 'baseonly');
    }


    /** @inheritDoc */
    function accepts($mode) {
        // Allow nesting the mode
        if ($mode === 'plugin_ifauthex') {
            return true;
        }
        return parent::accepts($mode);
    }

    /** @inheritDoc */
    function getPType()
    {
        return 'stack';
    }

    /** @inheritDoc */
    public function getSort()
    {
        return 195;
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
                    $exprPermission = (bool) $exprOrMatch->evaluate();
                    $shouldRender = $this->innermostShouldRender() && $exprPermission;

                    // Save the state only at the first occurrence
                    if ($this->innermostShouldRender() && !$exprPermission) {
                        if ($renderer->getFormat() === 'xhtml') {
                            // save the level so we can detect if we have opened a new section via a header or not
                            $renderer->meta['ifauthex.originalLevel'] = $renderer->getLastlevel();
                        }

                        // point the renderer's doc to something else, remembering the old one
                        $renderer->meta['ifauthex.originalDoc'] = &$renderer->doc;
                        $ignoredDoc = is_array($renderer->doc) ? [] : '';
                        $renderer->doc = &$ignoredDoc;

                        // do the same for the toc list
                        $ignoredToc = [];
                        $renderer->meta['ifauthex.originalToc'] = &$renderer->toc;
                        $renderer->toc = &$ignoredToc;

                        // patch the global TOC, if defined
                        global $TOC;
                        $renderer->meta['ifauthex.originalGlobalToc'] = &$TOC;
                        $TOC = is_array($TOC) ? [] : null;
                    }

                    // Push the new rendering permission
                    $this->pushInnerPermission($exprPermission);

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
                // point the renderer's doc and toc back to the original
                $shouldRenderOld = $this->popShouldRender();

                // Become active if innermostShouldRender changes from false to true
                if (!$shouldRenderOld && $this->innermostShouldRender()) {
                    $renderer->doc = &$renderer->meta['ifauthex.originalDoc'];
                    $renderer->toc = &$renderer->meta['ifauthex.originalToc'];

                    global $TOC;
                    $TOC = &$renderer->meta['ifauthex.originalGlobalToc'];

                    if ($renderer->getFormat() === 'xhtml') {
                        /*
                        Detect whether a section was opened in the meanwhile. This happens due to a header being issued inside
                        the hidden section. However, if we started at lev 0, and gotten at lev > 0, the lexer/parser combo will
                        have included a section_close command somewhere in the remaining part of the document.
                        We thus have to match that section_close with a corresponding section_open; the opening has occurred
                        within the hidden body, so we manually add a patch section_open entry.
                        See https://github.com/mittelab/ifauthex-dokuwiki-plugin/issues/8.
                        */
                        if ($renderer->meta['ifauthex.originalLevel'] === 0 && $renderer->getLastlevel() > 0) {
                            $renderer->section_open($renderer->getLastlevel());
                        }
                    }
                }
                break;
        }

        return true;
    }
}
