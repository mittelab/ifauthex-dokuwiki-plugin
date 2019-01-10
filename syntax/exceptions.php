<?php

namespace AST;
use \Exception;

class UnknownTokenException extends Exception {
    private $_text = null;
    private $_position = null;

    public function __construct($text, $position, $code = 0, Exception $previous = null) {
        $this->_text = $text;
        $this->_position = $position;
        $message = 'Unknown token "' . substr($text, $position, 4) . '" at position ' . $position;
        parent::__construct($message, $code, $previous);
    }

    public function getText() { return $this->_text; }
    public function getPosition() { return $this->_position; }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class MalformedExpressionException extends Exception {
    private $_elementInstance = null;

    public function __construct($elementInstance, $message, $code = 0, Exception $previous = null) {
        $this->_elementInstance = $elementInstance;
        parent::__construct($message, $code, $previous);
    }

    public function getElementInstance() { return $this->_elementInstance; }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class InvalidExpressionException extends Exception  {
    private $_elementInstance = null;

    public function __construct($elementInstance, $message, $code = 0, Exception $previous = null) {
        $this->_elementInstance = $elementInstance;
        parent::__construct($message, $code, $previous);
    }

    public function getElementInstance() { return $this->_elementInstance; }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class NotEnoughArgumentsException extends Exception {
    private $_elementDefinition = null;

    public function __construct($elementDefinition, $code = 0, Exception $previous = null) {
        $this->_elementDefinition = $elementDefinition;
        $message = 'Not enough arguments for operator ' . $elementDefinition->name() . '.';
        if ($elementDefinition->arity() > 0) {
            $message .= ' Expected ' . $elementDefinition->arity() . ' arguments.';
        }
        parent::__construct($message, $code, $previous);
    }

    public function getElementDefinition() { return $this->_elementDefinition; }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class StrayTokenException extends Exception {
    private $_tokenInstance = null;

    public function __construct($tokenInstance, $code = 0, Exception $previous = null) {
        $this->_tokenInstance = $tokenInstance;
        $message = 'Stray token encountered at position ' . $tokenInstance->position() . ', around "'
            . substr($tokenInstance->text(), max(0, $tokenInstance->position() - 3), $tokenInstance->length() + 3)
            . '".';
        parent::__construct($message, $code, $previous);
    }

    public function getTokenInstance() { return $this->_tokenInstance; }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class UnmatchedWrapperException extends Exception {
    private $_elementDefinition = null;
    private $_firstTokenInstance = null;

    public function __construct($elementDefinition, $firstTokenInstance, $code = 0, Exception $previous = null) {
        $this->_elementDefinition = $elementDefinition;
        $this->_firstTokenInstance = $firstTokenInstance;
        $message = 'Unmatched opening token ' . $elementDefinition->tokenDefs()[0] . ' for wrapping operator '
            . $elementDefinition->name() . ' encountered at position ' . $firstTokenInstance->position() . ', around "'
            . substr($firstTokenInstance->text(), max(0, $firstTokenInstance->position() - 3), $firstTokenInstance->length() + 3)
            . '". The missing closing token is ' . $elementDefinition->tokenDefs()[1] . '.';
        parent::__construct($message, $code, $previous);
    }

    public function getFirstTokenInstance() { return $this->_firstTokenInstance; }
    public function getElementDefinition() { return $this->_elementDefinition; }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

?>