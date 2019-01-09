<?php

require_once "grammar.php";

$text = 'usr1 ||(!usr2&&@group || !usr3)';
echo 'Parsing "' . $text . '".' . "\n";
$expr = parse($text);
$expr->printTree();
var_dump($expr->evaluate());

?>