<?php

include_once ( __DIR__."/config/globals.php" );

require_once ( __DIR__."/app/PathProcessor.php" );

use App\PathProcessor;

$path_processor             = new PathProcessor ();
    
$processed                  = $path_processor->process_path ( __DIR__."/files/ejemplo.php" );

echo $processed;