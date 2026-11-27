<?php

declare(strict_types=1);

require __DIR__ . '/lib.php';
require __DIR__ . '/frontend.php';
require __DIR__ . '/backend.php';
require __DIR__ . '/editor.php';

printf("\n%d checks, %d failed\n", $GLOBALS['checks'], $GLOBALS['failures']);

exit($GLOBALS['failures'] > 0 ? 1 : 0);
