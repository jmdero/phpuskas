<?php

namespace app;

class language_checker
{
    public      array   $languages                     = array ();

    private     array   $is_string                     = array ( "simple" => false, "double" => false );

    private     string  $default_language              = "html";

    private     string  $first_html                     = '';

    private     int     $counter_first_html             = 0;

    public function __construct ()
    {
        include_once ( __DIR__."/../config/languages.php" );
        
        foreach ( $languages as $language => $language_options )
        {
            if ( array_key_exists ( "start_rules", $language_options ) )
            {   
                $languages[$language]                          = $languages[$language_options["start_rules"]];
            }
        }

        $this->languages                                       = $languages;
    }

    public function check_structure ( string $line, array $file_structure ) : array
    {
        $character                                            = substr ( $line, 0, 1);

        $type                                                 = ( ( substr ( $line, 0, 1 ) === "<" ) && ( substr ( $line, 1, 1 ) !== "/" ) ) ? "start" : "end";

        if ( !in_array ( "true", array_values ( $this->is_string ) ) )
        {
            $finded                                            = false;
            
            foreach ( $this->languages as $language => $language_options )
            {
                if ( strpos ( $line, $language_options[$type] ) !== false )
                {
                    $exists                                    = ( end ( $file_structure ) === $language );
                    if ( ( ( $type === "start" ) && ( $exists ) ) || ( ( $type === "end" ) && ( !$exists ) ) ) { break; }

                    $confirm                                    = true;
                    
                    if ( ( array_key_exists( $type . "_confirm", $language_options ) ) && ( strpos ( $line, $language_options[$type . "_confirm"] ) === false ))
                    {
                        $confirm                                = false;
                    }

                    if ( !$confirm) { break; }

                    $is_possible                               = true;
                    
                    if ( ( array_key_exists( $type . "_confirm_not", $language_options ) ) && ( strpos ( $line, $language_options[$type . "_confirm_not"] ) !== false ) ) 
                    {
                        $is_possible                           = false;
                    }

                    if ( !$is_possible) { break; }
                    
                    echo $type.": ".$language.PHP_EOL;

                    ( $type === "start" ) ?  array_push ( $file_structure, $language ) : array_pop ( $file_structure );

                    $finded                                      = true;
                    
                    break;
                }
            }

            $condition                                          = ( $type === "start" ) ? ( end ( $file_structure ) !== $this->default_language ) : ( end ( $file_structure ) === $this->default_language );
            
            if ( ( !$finded ) && ( $condition ) )
            {
                $add                                            = true;

                $ends                                           = array_column ( $this->languages, "end" );

                foreach ( $ends as $end )
                {
                    if ( strpos ( $line, $end ) !== false )
                    {
                        $add                                    = false;
                        
                        break;
                    }
                }

                if ( $add )
                {
                    if ( $type === "start" )
                    {
                        echo $type . ": ".$this->default_language.PHP_EOL;

                        $this->first_html                        = str_replace ( ["<", ">"], ["", ""], strtok( $line, " " ) );

                        $this->counter_first_html++;

                        array_push ( $file_structure, $this->default_language );
                    }
                    else{

                        if ( strpos ( $line, "</" . $this->first_html . ">"  ) !== false )
                        {
                            $this->counter_first_html --;
                        }
                        
                        if ( $this->counter_first_html == 0 )
                        {
                            echo $type . ": ".$this->default_language.PHP_EOL;

                            $this->first_html                     = "";

                            array_pop ( $file_structure );
                        }
                    }
                }
            }
        } 
        else if ( ( $character === '"' ) || ( $character === "'" ) )
        {
            $type_string                                         = ( $character === "'" ) ? "simple" : "double";

            $this->is_string[$type_string]                       = !$this->is_string[$type_string];
        }

        return $file_structure;
    }
}