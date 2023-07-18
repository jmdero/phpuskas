<?php 

function get_characters ( string $line )
{
    $line                               = ( substr( $line, -1 ) === " " ) ? substr( $line, 0, -1 ) : $line ;

    return str_split( $line );
 }