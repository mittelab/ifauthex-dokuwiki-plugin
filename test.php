<?php

require_once "grammar.php";

$text = 'usr1 ||(!usr2&&@group || !usr3)';
echo 'Parsing "' . $text . '".' . "\n";
$expr = parse($text);
echo "Tree representation:\n";
$expr->printTree();
echo "Value:\n";
var_dump($expr->evaluate());
echo "Reconstructed expression:\n";
echo $expr->getRepresentation() . "\n";
?>