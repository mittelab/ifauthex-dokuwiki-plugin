<?php

namespace AST;
use \InvalidArgumentException;
use \LogicException;
use \RuntimeException;

require_once "tokenizer.php";
require_once "exceptions.php";

$STACK_LIMIT = 50;

abstract class Fixing {
    const None = 0;
    const Prefix = 1;
    const Postfix = 2;
    const Infix = 3;
    const Wrap = 4;
}

class ElementInstance {
    private $_definition = null;
    private $_args = null;

    public function __construct($definition, $args) {
        $this->_definition = $definition;
        $this->_args = $args;
    }

    public function definition() { return $this->_definition; }
    public function args() { return $this->_args; }

    public function getStringValue() {
        $pieces = array();
        foreach ($this->args() as $arg) {
            if ($arg instanceof ElementInstance) {
                $pieces[] = $arg->getStringValue();
            } else {
                $pieces[] = $arg->match();
            }
        }
        return implode('', $pieces);
    }

    public function getRepresentation() {
        static $stack_depth = 0; global $STACK_LIMIT;
        if (++$stack_depth > $STACK_LIMIT) {
            throw new RuntimeException('Stack limit exceeded.');
        }
        try {
            if ($this->definition() === null) {
                $this->ensureWellFormed(false);
                // The expression is guaranteed to have 0 or 1 arguments because it's well formed.
                if (count($this->args()) == 0) {
                    return '';
                }
                if ($this->args()[0] instanceof ElementInstance) {
                    return $this->args()[0]->getRepresentation();
                } else {
                    return $this->args()[0]->match();
                }
            } else {
                return $this->definition()->getRepresentation($this);
            }
        } finally {
            --$stack_depth;
        }
    }

    public function isExpanded($recursive=true) {
        static $stack_depth = 0; global $STACK_LIMIT;
        if (++$stack_depth > $STACK_LIMIT) {
            throw new RuntimeException('Stack limit exceeded.');
        }
        try {
            if ($this->definition() !== null && $this->definition()->fixing() == Fixing::None) {
                return true;
            }
            foreach ($this->args() as $arg) {
                if ($arg instanceof TokenInstance || ($recursive && !$arg->isExpanded($recursive))) {
                    return false;
                }
            }
        } finally {
            --$stack_depth;
        }
        return true;
    }

    public function expand($elmDef, $recursive=true) {
        static $stack_depth = 0; global $STACK_LIMIT;
        if (++$stack_depth > $STACK_LIMIT) {
            throw new RuntimeException('Stack limit exceeded.');
        }
        try {
            if ($this->isExpanded($recursive)) {
                return;
            }
            $elmDef->spliceInstancesIn($this->_args);
            if ($recursive) {
                foreach ($this->args() as $arg) {
                    if ($arg instanceof ElementInstance && !$arg->isExpanded($recursive)) {
                        $arg->expand($elmDef);
                    }
                }
            }
        } finally {
            --$stack_depth;
        }
    }

    public function findUnexpandedToken($recursive=true) {
        static $stack_depth = 0; global $STACK_LIMIT;
        if (++$stack_depth > $STACK_LIMIT) {
            throw new RuntimeException('Stack limit exceeded.');
        }
        try {
            if ($this->isExpanded($recursive)) {
                return null;
            }
            foreach ($this->args() as $arg) {
                if ($arg instanceof TokenInstance) {
                    return $arg;
                } elseif ($recursive) {
                    $tok = $arg->findUnexpandedToken($recursive);
                    if ($tok !== null) {
                        return $tok;
                    }
                }
            }
            throw LogicException('A fully expanded element instance has no stray token!');
        } finally {
            --$stack_depth;
        }
    }

    public function printTree($indent=0) {
        static $stack_depth = 0; global $STACK_LIMIT;
        if (++$stack_depth > $STACK_LIMIT) {
            throw new RuntimeException('Stack limit exceeded.');
        }
        try {
            if ($this->definition() !== null) {
                echo str_repeat('  ', $indent) . $this->definition()->name() . "\n";
            }
            foreach ($this->args() as $arg) {
                if ($arg instanceof TokenInstance) {
                    echo str_repeat('  ', $indent + 1) . $arg;
                } else {
                    $arg->printTree($indent + 1);
                }
            }
        } finally {
            --$stack_depth;
        }
    }

    public function evaluateArgs() {
        $values = array();
        foreach ($this->args() as $arg) {
            if ($arg instanceof ElementInstance) {
                $values[] = $arg->evaluate();
            } else {
                $values[] = $arg;
            }
        }
        return $values;
    }

    public function ensureWellFormed($recursive=true) {
        static $stack_depth = 0; global $STACK_LIMIT;
        if (++$stack_depth > $STACK_LIMIT) {
            throw new RuntimeException('Stack limit exceeded.');
        }
        try {
            if ($this->definition() === null) {
                if (count($this->args()) > 1) {
                    throw new MalformedExpressionException($this, 'An expression that has more than a single root is not well defined.');
                }
            } else {
                $this->definition()->ensureWellFormed($this);
            }
            if ($recursive) {
                foreach ($this->args() as $arg) {
                    if ($arg instanceof ElementInstance) {
                        $arg->ensureWellFormed($recursive);
                    }
                }
            }
        } finally {
            --$stack_depth;
        }
    }

    public function evaluate() {
        static $stack_depth = 0; global $STACK_LIMIT;
        if (++$stack_depth > $STACK_LIMIT) {
            throw new RuntimeException('Stack limit exceeded.');
        }
        try {
            if ($this->definition() === null) {
                $this->ensureWellFormed(false);
                // The expression is guaranteed to have 0 or 1 arguments because it's well formed.
                if (count($this->args()) == 0) {
                    return null;
                }
                return $this->evaluateArgs()[0];
            } else {
                return $this->definition()->evaluate($this);
            }
        } finally {
            --$stack_depth;
        }
    }
}

class ElementDefinition {
    private $_name = null;
    private $_arity = null;
    private $_nested = null;
    private $_fixing = null;
    private $_priority = null;
    private $_tokenDefs = null;

    public function arity() { return $this->_arity; }
    public function nested() { return $this->_nested; }
    public function fixing() { return $this->_fixing; }
    public function priority() { return $this->_priority; }
    public function tokenDefs() { return $this->_tokenDefs; }
    public function name() { return $this->_name; }

    public function __construct($name, $fixing, $tokenDefs, $priority, $arity=null, $nested=null) {
        if ($tokenDefs instanceof TokenDefinition) {
            $tokenDefs = array($tokenDefs);
        }
        if (!is_array($tokenDefs)) {
            throw new InvalidArgumentException('tokenDefs can only be a TokenDefinition or an array of them.');
        }
        if ($arity === null) {
            switch ($fixing) {
                case Fixing::None:
                    $arity = 0;
                    break;
                case Fixing::Prefix:
                case Fixing::Postfix:
                    $arity = 1;
                    break;
                case Fixing::Infix:
                    $arity = -1;
                    break;
                case Fixing::Wrap:
                    break;
                default:
                    throw new InvalidArgumentException('Invalid fixing.');
                    break;
            }
        }
        $this->_name = $name;
        $this->_tokenDefs = $tokenDefs;
        $this->_arity = $arity;
        $this->_nested = $nested;
        $this->_fixing = $fixing;
        $this->_priority = $priority;

        switch ($this->fixing()) {
            case Fixing::None:
                if ($this->arity() != 0) {
                    throw new LogicException('An element with no fixing must be 0-ary.');
                }
                if (count($this->tokenDefs()) != 1) {
                    throw new LogicException('An element with no fixing must have exactly 1 token.');
                }
                break;
            case Fixing::Prefix:
                if ($this->arity() == 0) {
                    throw new LogicException('An prefix element must be n-ary with n > 0.');
                }
                if (count($this->tokenDefs()) != 1 && count($this->tokenDefs()) != $this->arity()) {
                    throw new LogicException('A n-ary prefix operator must have either 1 or n tokens.');
                }
                break;
            case Fixing::Postfix:
                if ($this->arity() == 0) {
                    throw new LogicException('An postfix element must be n-ary with n > 0.');
                }
                if (count($this->tokenDefs()) != 1 && count($this->tokenDefs()) != $this->arity()) {
                    throw new LogicException('A n-ary postfix operator must have either 1 or n tokens.');
                }
                break;
            case Fixing::Infix:
                if ($this->arity() == 0 || $this->arity() == 1) {
                    throw new LogicException('An infix element must be n-ary with n > 1.');
                }
                if (count($this->tokenDefs()) != 1 && count($this->tokenDefs()) != $this->arity() - 1) {
                    throw new LogicException('A n-ary infix operator must have either 1 or n-1 tokens.');
                }
                break;
            case Fixing::Wrap:
                if ($this->arity() !== null) {
                    throw new LogicException('Arity does not apply to a wrapping element.');
                }
                if (count($this->tokenDefs()) != 2) {
                    throw new LogicException('Wrapping operators are identified by exactly two tokens.');
                }
                break;
            default:
                throw new InvalidArgumentException('Invalid fixing specified.');
                break;
        }

        if ($this->fixing() == Fixing::Wrap) {
            if ($this->nested() === null) {
                throw new LogicException('You must specify whether a wrapping operator is nested.');
            }
        } else {
            if ($this->nested() !== null) {
                throw new LogicException('Nested applies only to wrapping operators.');
            }
        }
    }

    private static function _getLongestAlternateChain($args, $position, $tokDef, $stopAt=-1) {
        $nFound = 0;
        for ($lastFound = $position; $lastFound < count($args); $lastFound += 2) {
            if ($args[$lastFound]->definition() == $tokDef) {
                if ($stopAt >= 0 && $nFound >= $stopAt) {
                    break;
                }
                ++$nFound;
            } else {
                break;
            }
        }
        return $nFound;
    }

    private static function _isMatchingAlternateChain($args, $position, $tokDefs) {
        $tokDefIdx = 0;
        for ($lastFound = $position; $lastFound < count($args) && $tokDefIdx < count($tokDefs); $lastFound += 2) {
            if ($args[$lastFound]->definition() == $tokDefs[$tokDefIdx]) {
                ++$tokDefIdx;
            } else {
                return false;
            }
        }
        return ($tokDefIdx == count($tokDefs));
    }

    private static function _getWrappedSequence($args, $position, $tokDefs, $nested) {
        if (count($tokDefs) != 2) {
            throw new LogicException('Wrapping operators must have exactly 2 tokens.');
        }
        list($openTokDef, $closeTokDef) = $tokDefs;
        if ($args[$position]->definition() != $openTokDef) {
            return 0;
        }
        if ($nested) {
            // Get the longest sequence
            for ($i = count($args) - 1; $i > $position; --$i) {
                if ($args[$i]->definition() == $closeTokDef) {
                    return $i - $position + 1;
                }
            }
        } else {
            // Get the shortest sequence
            for ($i = $position + 1; $i < count($args); ++$i) {
                if ($args[$i]->definition() == $closeTokDef) {
                    return $i - $position + 1;
                }
            }
        }
        return 1;  // Which means unmatched sequence
    }

    private static function _extractAlternateChain($args, $position, $length) {
        $retval = array();
        for ($i = $position; $i < count($args) && count($retval) < $length; $i += 2) {
            $retval[] = $args[$i];
        }
        return $retval;
    }

    private static function _splicePrefix(&$args, $firstTokPosition, $chainLength, $definition) {
        if ($firstTokPosition < 0) {
            throw new InvalidArgumentException('Attempt to _splicePrefix with a negative offset.');
        }
        $elmArgs = self::_extractAlternateChain($args, $firstTokPosition + 1, $chainLength);
        $elmInst = new ElementInstance($definition, $elmArgs);
        if ($firstTokPosition + $chainLength * 2 > count($args)) {
            throw new NotEnoughArgumentsException($definition, $args[$firstTokPosition]);
        }
        array_splice($args, $firstTokPosition, $chainLength * 2, array($elmInst));
        return $firstTokPosition;
    }

    private static function _splicePostfix(&$args, $firstTokPosition, $chainLength, $definition) {
        if ($firstTokPosition < 0) {
            throw new InvalidArgumentException('Attempt to _splicePostfix with a negative offset.');
        }
        $elmArgs = self::_extractAlternateChain($args, $firstTokPosition - 1, $chainLength);
        $elmInst = new ElementInstance($definition, $elmArgs);
        if ($firstTokPosition == 0) {
            throw new NotEnoughArgumentsException($definition, $args[$firstTokPosition]);
        } elseif ($firstTokPosition + $chainLength * 2 - 1 > count($args)) {
            throw new NotEnoughArgumentsException($definition, $args[$firstTokPosition - 1]);
        }
        array_splice($args, $firstTokPosition - 1, $chainLength * 2, array($elmInst));
        return $firstTokPosition - 1;
    }

    private static function _spliceInfix(&$args, $firstTokPosition, $chainLength, $definition) {
        if ($firstTokPosition < 0) {
            throw new InvalidArgumentException('Attempt to _spliceInfix with a negative offset.');
        }
        $elmArgs = self::_extractAlternateChain($args, $firstTokPosition - 1, $chainLength + 1);
        $elmInst = new ElementInstance($definition, $elmArgs);
        if ($firstTokPosition == 0) {
            throw new NotEnoughArgumentsException($definition, $args[$firstTokPosition]);
        } elseif ($firstTokPosition + $chainLength * 2 > count($args)) {
            throw new NotEnoughArgumentsException($definition, $args[$firstTokPosition - 1]);
        }
        array_splice($args, $firstTokPosition - 1, $chainLength * 2 + 1, array($elmInst));
        return $firstTokPosition - 1;
    }

    private static function _spliceWrap(&$args, $firstTokPosition, $sequenceLength, $definition) {
        if ($firstTokPosition < 0) {
            throw new InvalidArgumentException('Attempt to _spliceWrap with a negative offset.');
        }
        if ($sequenceLength < 2) {
            throw new LogicException('A wrapping sequence must consist of at least the two wrapping tokens.');
        }
        if ($firstTokPosition + $sequenceLength > count($args)) {
            throw new LogicException('You requested to cut a sequence longer than the number of tokens.');
        }
        $elmArgs = array_slice($args, $firstTokPosition + 1, $sequenceLength - 2);
        $elmInst = new ElementInstance($definition, $elmArgs);
        array_splice($args, $firstTokPosition, $sequenceLength, array($elmInst));
        return $firstTokPosition;
    }

    private static function _spliceNone(&$args, $firstTokPosition, $definition) {
        if ($firstTokPosition < 0) {
            throw new InvalidArgumentException('Attempt to _spliceNone with a negative offset.');
        }
        if ($firstTokPosition + 1 > count($args)) {
            throw new LogicException('You requested to cut a token at the end of the tokens array.');
        }
        array_splice($args, $firstTokPosition, 1, array(new ElementInstance($definition, array($args[$firstTokPosition]))));
        return $firstTokPosition;
    }

    public function trySpliceAt(&$args, &$position) {
        switch ($this->fixing()) {
            case Fixing::None:
                if ($args[$position]->definition() == $this->tokenDefs()[0]) {
                    $position = self::_spliceNone($args, $position, $this);
                    return true;
                }
                break;
            case Fixing::Prefix:
                if ($this->arity() < 0) {
                    $chainLength = self::_getLongestAlternateChain($args, $position, $this->tokenDefs()[0]);
                    if ($chainLength > 0) {
                        $position = self::_splicePrefix($args, $position, $chainLength, $this);
                        return true;
                    }
                } else if (count($this->tokenDefs()) == 1) {
                    $chainLength = self::_getLongestAlternateChain($args, $position, $this->tokenDefs()[0], $this->arity());
                    if ($chainLength == $this->arity()) {
                        $position = self::_splicePrefix($args, $position, $chainLength, $this);
                        return true;
                    }
                } else if (self::_isMatchingAlternateChain($args, $position, $this->tokenDefs())) {
                    $position = self::_splicePrefix($args, $position, $this->arity(), $this);
                    return true;
                }
                break;
            case Fixing::Postfix:
                if ($this->arity() < 0) {
                    $chainLength = self::_getLongestAlternateChain($args, $position + 1, $this->tokenDefs()[0]);
                    if ($chainLength > 0) {
                        $position = self::_splicePostfix($args, $position + 1, $chainLength, $this);
                        return true;
                    }
                } else if (count($this->tokenDefs()) == 1) {
                    $chainLength = self::_getLongestAlternateChain($args, $position + 1, $this->tokenDefs()[0], $this->arity());
                    if ($chainLength == $this->arity()) {
                        $position = self::_splicePostfix($args, $position + 1, $chainLength, $this);
                        return true;
                    }
                } else if (self::_isMatchingAlternateChain($args, $position + 1, $this->tokenDefs())) {
                    $position = self::_splicePostfix($args, $position + 1, $this->arity(), $this);
                    return true;
                }
                break;
            case Fixing::Infix:
                if ($this->arity() < 0) {
                    $chainLength = self::_getLongestAlternateChain($args, $position + 1, $this->tokenDefs()[0]);
                    if ($chainLength > 0) {
                        $position = self::_spliceInfix($args, $position + 1, $chainLength, $this);
                        return true;
                    }
                } else if (count($this->tokenDefs()) == 1) {
                    $chainLength = self::_getLongestAlternateChain($args, $position + 1, $this->tokenDefs()[0], $this->arity());
                    if ($chainLength == $this->arity() - 1) {
                        $position = self::_spliceInfix($args, $position + 1, $chainLength, $this);
                        return true;
                    }
                } else if (self::_isMatchingAlternateChain($args, $position + 1, $this->tokenDefs())) {
                    $position = self::_spliceInfix($args, $position + 1, $this->arity() - 1, $this);
                    return true;
                }
                break;
            case Fixing::Wrap:
                $sequenceLength = self::_getWrappedSequence($args, $position, $this->tokenDefs(), $this->nested());
                if ($sequenceLength >= 2) {
                    $position = self::_spliceWrap($args, $position, $sequenceLength, $this);
                    return true;
                } elseif ($sequenceLength == 1) {
                    throw new UnmatchedWrapperException($this, $args[$position]);
                }
                break;
        }
    }

    public function spliceInstancesIn(&$args) {
        $somethingHappened = false;
        for ($i = 0; $i < count($args); ++$i) {
            if ($this->trySpliceAt($args, $i)) {
                $somethingHappened = true;
            }
        }
        return $somethingHappened;
    }

    public function _evaluateWellFormed($elmInstance) {
        return null;
    }

    public function evaluate($elmInstance) {
        $this->ensureWellFormed($elmInstance);
        return $this->_evaluateWellFormed($elmInstance);
    }

    public function getTokenDefRepr($idx) {
        if ($idx < count($this->tokenDefs())) {
            return $this->tokenDefs()[$idx]->representation();
        } elseif (count($this->tokenDefs()) == 1) {
            return $this->tokenDefs()[0]->representation();
        }
        return '';
    }

    public function getRepresentation($elmInstance) {
        $this->ensureWellFormed($elmInstance);
        $argsRepr = array();
        foreach ($elmInstance->args() as $arg) {
            if ($arg instanceof TokenInstance) {
                $argsRepr[] = $arg->match();
            } else {
                $argsRepr[] = $arg->getRepresentation();
            }
        }
        $pieces = array();
        if ($this->fixing() == Fixing::Prefix || $this->fixing() == Fixing::Wrap) {
            $pieces[] = $this->getTokenDefRepr(0);
        }
        $argIdx = ($this->fixing() == Fixing::Postfix || $this->fixing() == Fixing::Infix ? -1 : 0);
        foreach ($argsRepr as $argRepr) {
            $pieces[] = $argRepr;
            ++$argIdx;
            if ($this->fixing() == Fixing::None && $this->fixing() == Fixing::Wrap) {
                continue;
            }
            if ($argIdx >= count($argsRepr) || ($this->fixing() == Fixing::Infix && $argIdx >= count($argsRepr) - 1)) {
                continue;
            }
            $pieces[] = $this->getTokenDefRepr($argIdx);
        }
        if ($this->fixing() == Fixing::Wrap) {
            $pieces[] = $this->getTokenDefRepr(1);
        }
        return implode('', $pieces);
    }

    static public function extractUsedTokens($elmDefs) {
        $retval = array();
        foreach ($elmDefs as $elmDef) {
            $retval = array_merge($retval, $elmDef->tokenDefs());
        }
        return $retval;
    }

    public function ensureWellFormed($elmInstance) {
        if ($elmInstance->definition() != $this) {
            throw new LogicException('This instance is not an instance of the given definition.');
        }
        $args = $elmInstance->args();
        // Check arity
        switch ($this->fixing()) {
            case Fixing::None:
                if (count($args) != 1) {
                    throw new MalformedExpressionException($elmInstance, 'Expected exactly one token.');
                } else if (!($args[0] instanceof TokenInstance)) {
                    throw new MalformedExpressionException($elmInstance, 'Expected a token instance.');
                } else if ($args[0]->definition() != $this->tokenDefs()[0]) {
                    throw new MalformedExpressionException($elmInstance, 'Expected a token of type ' . $this->tokenDefs()[0]->name());
                }
                break;
            case Fixing::Prefix:
            case Fixing::Postfix:
            case Fixing::Infix:
                if ($this->arity() < 0) { // Any number of tokens, > 0
                    if (count($args) < 1 && $this->fixing() == Fixing::Infix) {
                        throw new MalformedExpressionException($elmInstance, 'Expected at least two arguments.');
                    } elseif (count($args) == 0) {
                        throw new MalformedExpressionException($elmInstance, 'Expected at least one argument.');
                    }
                } elseif (count($args) != $this->arity()) {
                    throw new MalformedExpressionException($elmInstance, 'Expected exactly ' . $this->arity() . ' arguments.');
                }
                break;
        }
    }
}

function parse(array $tokInsts, array $elmDefs) {
    usort($elmDefs, function ($a, $b) { return $a->priority() - $b->priority(); });
    $root = new ElementInstance(null, $tokInsts);
    foreach ($elmDefs as $elmDef) {
        $root->expand($elmDef);
    }
    if (!$root->isExpanded()) {
        throw new StrayTokenException($root->findUnexpandedToken());
    }
    return $root;
}

?>