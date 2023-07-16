<?php

namespace app;

class language_checker
{
    public  array   $languages  = array ();

    public function __construct ()
    {
        include_once ( __DIR__."/../config/languages.php" );
        
        foreach ( $languages as $language => $language_options )
        {
            if ( array_key_exists ( "start_rules", $language_options ) )
            {   
                $languages[$language]     = $languages[$language_options["start_rules"]];
            }
        }

        $this->languages        = $languages;
    }

    public function check_start ( string $line, array $file_structure ) : array
    {
        if ( substr ( $line, 0, 1 ) === "<")
        {
            foreach ( $this->languages as $language )
            {
               
            }
        }
        return $file_structure;
    }

    public function check_end ( string $line, array $file_structure ) : array
    {
        return $file_structure;
    }
}