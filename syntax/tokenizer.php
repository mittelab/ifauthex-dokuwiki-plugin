<?php

namespace AST;
use \InvalidArgumentException;
use \RuntimeException;

require_once "exceptions.php";

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
            $matchRegex = '/' . preg_quote($representation) . '/';
        }
        $this->_matchRegex = $matchRegex;
    }

    public function representation() { return $this->_representation;     }
    public function name() { return $this->_name; }

    public function tryMatch($text, $position) {
        $matches = null;
        $result = preg_match($this->_matchRegex, $text, $matches, PREG_OFFSET_CAPTURE, $position);
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
    public function __toString() {
        return '<' . $this->name() . ">\n";
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
    public function match() { return substr($this->_text, $this->position(), $this->length()); }

    public function __toString() {
        return '<' . $this->definition()->name() . ':' . $this->match() . ">\n";
    }
}

function tokenize(string $text, array $tokDefs, array $stripTokDefs) {
    $tokInsts = array();
    $foundTokInst = null;
    for ($position = 0; $position < strlen($text); $position += $foundTokInst->length()) {
        $foundTokInst = null;
        foreach ($tokDefs as $tokDef) {
            $match = $tokDef->tryMatch($text, $position);
            if ($match !== null) {
                $foundTokInst = new TokenInstance($tokDef, $text, $position, strlen($match));
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