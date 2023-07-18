<?php

include_once ( __DIR__."/config/globals.php" );

include_once ( __DIR__."/functions/text_reader.php" );

require_once ( __DIR__."/app/path_processor.php" );

use app\path_processor;

$path_processor             = new path_processor ();
    
$processed                  = $path_processor->process_path ( __DIR__."/files/ejemplo.php" );

echo $processed;