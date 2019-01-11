<?php

require_once "parser.php";
require_once "tokenizer.php";
require_once "exceptions.php";

use \AST\ElementDefinition;
use \AST\TokenDefinition;
use \AST\InvalidExpressionException;
use \AST\MalformedExpressionException;
use \AST\Fixing;

class Literal extends ElementDefinition {
    public function __construct() {
        $T_LITERAL = new TokenDefinition(null, 'LIT', '/\w+/');
        parent::__construct('Literal', Fixing::None, $T_LITERAL, 0);
    }
    public function _evaluateWellFormed($elmInstance) {
        $key = 'REMOTE_USER';
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key] == $elmInstance->getStringValue();
        }
        return false;
    }
}

class SubExpr extends ElementDefinition {
    public function __construct() {
        $T_OPEN_PAREN = new TokenDefinition('(', 'OPENP');
        $T_CLOSE_PAREN = new TokenDefinition(')', 'CLOSEP');
        parent::__construct('SubExpr', Fixing::Wrap, array($T_OPEN_PAREN, $T_CLOSE_PAREN), 1, null, true);
    }
    public function ensureWellFormed($elmInstance) {
        parent::ensureWellFormed($elmInstance);
        if (count($elmInstance->args()) != 1) {
            throw new MalformedExpressionException($elmInstance, 'A subexpression must have exactly one root');
        }
    }
    public function _evaluateWellFormed($elmInstance) {
        return $elmInstance->evaluateArgs()[0];
    }
}

class OpInGroup extends ElementDefinition {
    public function __construct() {
        $T_AT = new TokenDefinition('@', 'AT');
        parent::__construct('InGroup', Fixing::Prefix, $T_AT, 2);
    }
    public function ensureWellFormed($elmInstance) {
        parent::ensureWellFormed($elmInstance);
        if (!($elmInstance->args()[0]->definition() instanceof Literal)) {
            throw new MalformedExpressionException($elmInstance, 'A in-group operator <@> must take exactly one literal argument.');
        }
    }
    public function _evaluateWellFormed($elmInstance) {
        $groupName = $elmInstance->args()[0]->getStringValue();
        global $INFO;
        $key1 = 'userinfo';
        $key2 = 'grps';
        if (is_array($INFO) && array_key_exists($key1, $INFO)) {
            if (is_array($INFO[$key1]) && array_key_exists($key2, $INFO[$key1])) {
                return in_array($groupName, $INFO[$key1][$key2]);
            }
        }
        return false;
    }
}

class OpNot extends ElementDefinition {
    public function __construct() {
        $T_EXCL = new TokenDefinition('!', 'EXCL');
        parent::__construct('Not', Fixing::Prefix, $T_EXCL, 3);
    }
    public function _evaluateWellFormed($elmInstance) {
        $argValues = $elmInstance->evaluateArgs();
        if (!is_bool($argValues[0])) {
            throw new InvalidExpressionException($elmInstance, 'Not called on a non-boolean argument.');
        }
        return !$argValues[0];
    }
}

class OpAnd extends ElementDefinition {
    public function __construct() {
        $T_AND = new TokenDefinition('&&', 'AND');
        parent::__construct('And', Fixing::Infix, $T_AND, 4);
    }
    public function _evaluateWellFormed($elmInstance) {
        $argValues = $elmInstance->evaluateArgs();
        foreach ($argValues as $arg) {
            if (!is_bool($arg)) {
                throw new InvalidExpressionException($elmInstance, 'And called on non-boolean arguments.');
            }
            if (!$arg) {
                return false;
            }
        }
        return true;
    }
}

class OpOr extends ElementDefinition {
    public function __construct() {
        $T_OR = new TokenDefinition('||', 'OR', '/(\|\||,)/');
        parent::__construct('Or', Fixing::Infix, $T_OR, 5);
    }
    public function _evaluateWellFormed($elmInstance) {
        $argValues = $elmInstance->evaluateArgs();
        foreach ($argValues as $arg) {
            if (!is_bool($arg)) {
                throw new InvalidExpressionException($elmInstance, 'Or called on non-boolean arguments.');
            }
            if ($arg) {
                return true;
            }
        }
        return false;
    }
}

function auth_expr_all_elements() {
    static $ALL_ELEMENTS = null;
    if ($ALL_ELEMENTS === null) {
        $ALL_ELEMENTS = array(new Literal(), new SubExpr(), new OpInGroup(), new OpNot(), new OpAnd(), new OpOr());
    }
    return $ALL_ELEMENTS;
}

function auth_expr_ignore_tokens() {
    static $IGNORE_TOKENS = null;
    if ($IGNORE_TOKENS === null) {
        $IGNORE_TOKENS = array(new TokenDefinition(' ', 'SPC', '/\s+/'));
    }
    return $IGNORE_TOKENS;
}

function auth_expr_all_tokens() {
    static $ALL_TOKENS = null;
    if ($ALL_TOKENS === null) {
        $ALL_TOKENS = array_merge(auth_expr_ignore_tokens(), ElementDefinition::extractUsedTokens(auth_expr_all_elements()));
    }
    return $ALL_TOKENS;
}

function auth_expr_parse($expr) {
    return \AST\parse(\AST\tokenize($expr, auth_expr_all_tokens(), auth_expr_ignore_tokens()), auth_expr_all_elements());
}

?>