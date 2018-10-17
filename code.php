<?php
include 'library/init.inc.php';

$code = new Code(array('line'=>false, 'snow'=>false));

$code->doimg();
$_SESSION['code'] = $code->getCode();
?>