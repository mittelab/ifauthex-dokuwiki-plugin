<?php

namespace AST;
use \InvalidArgumentException;
use \RuntimeException;

require_once "exceptions.php";


function mb_preg_adapt_regex($regex) {
    if (strlen($regex) < 2) {
        return array($regex, '');
    }
    // "/rgx/options" --> ["rgx", "options"]
    $lastDelim = strrpos($regex, $regex[0]);
    if ($lastDelim === false || $lastDelim < 1) {
        return array($regex, '');
    }
    $options = substr($regex, $lastDelim + 1);
    $regex = substr($regex, 1, $lastDelim - 1);
    return array($regex, $options);
}

function mb_preg_match($text, $matchRegex, $position=0) {
    mb_regex_encoding('UTF-8');
    list($matchRegex, $matchRegexOptions) = mb_preg_adapt_regex($matchRegex);
    $textPiece = mb_substr($text, $position);
    if (mb_ereg_search_init($textPiece) === false) {
        return null;
    }
    $result = mb_ereg_search_pos($matchRegex, $matchRegexOptions);
    list($matchOfsBytes, $matchLenBytes) = $result;
    // Is the match offset in byte coinciding with the specified position?
    // Or: is the match exactly at $position?
    if (mb_strlen(mb_strcut($textPiece, 0, $matchOfsBytes)) > 0) {
        return null;
    }
    return mb_strcut($textPiece, $matchOfsBytes, $matchLenBytes);
}


function sb_preg_match($text, $matchRegex, $position=0) {
    $matches = null;
    $result = preg_match($matchRegex, $text, $matches, PREG_OFFSET_CAPTURE, $position);
    if ($result === 0) {
        return null;
    } elseif ($result === false) {
        throw new InvalidArgumentException('An error occurred in preg_match.');
    } elseif (count($matches) == 0) {
        throw new RuntimeException('No matches?');
    }
    list($matchTxt, $matchOfs) = $matches[0];
    if ($matchOfs > $position) {
        return null;
    }
    return $matchTxt;
}



class TokenDefinition {
    private $_representation = null;
    private $_name = null;
    private $_matchRegex = null;

    public function __construct($representation, $name=null, $matchRegex=null) {
        $this->_representation = $representation;
        if ($name === null) {
            $name = $representation;
        }
        $this->_name = $name;
        if ($matchRegex === null) {
            // preg_quote works also for multibyte
            // https://stackoverflow.com/a/31733257/1749822
            $matchRegex = '/' . preg_quote($representation) . '/';
        }
        $this->_matchRegex = $matchRegex;
    }

    public function representation() { return $this->_representation; }
    public function name() { return $this->_name; }

    public static function supportsMultibyte() {
        static $_loaded = null;
        if ($_loaded === null) {
            $_loaded = (extension_loaded('mbstring') === true);
        }
        return $_loaded;
    }

    public function tryMatch($text, $position) {
        if (self::supportsMultibyte()) {
            return mb_preg_match($text, $this->_matchRegex, $position);
        } else {
            return sb_preg_match($text, $this->_matchRegex, $position);
        }
    }

    public function __toString() {
        return '<' . $this->name() . '>';
    }
}

class TokenInstance {
    private $_definition = null;
    private $_text = null;
    private $_position = null;
    private $_length = null;

    public function __construct($definition, $text, $position, $length) {
        $this->_definition = $definition;
        $this->_text = $text;
        $this->_position = $position;
        $this->_length = $length;
    }

    public function definition() { return $this->_definition; }
    public function text() { return $this->_text; }
    public function position() { return $this->_position; }
    public function length() { return $this->_length; }
    public function match() {
        if (TokenDefinition::supportsMultibyte()) {
            return mb_substr($this->_text, $this->position(), $this->length());
        } else {
            return substr($this->_text, $this->position(), $this->length());
        }
    }

    public function __toString() {
        return '<' . $this->definition()->name() . ':' . $this->match() . '>';
    }
}

function tokenize($text, array $tokDefs, array $stripTokDefs) {
    if (TokenDefinition::supportsMultibyte()) {
        $textLen = mb_strlen($text);
    } else {
        $textLen = strlen($text);
    }

    $tokInsts = array();
    $foundTokInst = null;
    for ($position = 0; $position < $textLen; $position += $foundTokInst->length()) {
        $foundTokInst = null;
        foreach ($tokDefs as $tokDef) {
            $match = $tokDef->tryMatch($text, $position);
            if ($match !== null) {
                if (TokenDefinition::supportsMultibyte()) {
                    $matchLen = mb_strlen($match);
                } else {
                    $matchLen = strlen($match);
                }
                $foundTokInst = new TokenInstance($tokDef, $text, $position, $matchLen);
                break;
            }
        }
        if ($foundTokInst === null) {
            throw new UnknownTokenException($text, $position);
        } elseif (!in_array($foundTokInst->definition(), $stripTokDefs)) {
            $tokInsts[] = $foundTokInst;
        }
    }
    return $tokInsts;
}

?>