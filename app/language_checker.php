<?php

namespace app;

class language_checker
{
    public      array   $languages                     = array ();

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
        $type                                                 = ( ( substr ( $line, 0, 1 ) === "<" ) && ( substr ( $line, 1, 1 ) !== "/" ) ) ? "start" : "end";

        $finded                                                = false;
        
        foreach ( $this->languages as $language => $language_options )
        {   
            if ( substr( $line, 0, strlen ( $language_options[$type] ) ) === $language_options[$type] )
            {
                $exists                                        = ( end ( $file_structure ) === $language );
                
                if ( ( ( $type === "start" ) && ( $exists ) ) || ( ( $type === "end" ) && ( !$exists ) ) ) { break; }

                $confirm                                        = true;
                
                if ( ( array_key_exists( $type . "_confirm", $language_options ) ) && ( strpos ( $line, $language_options[$type . "_confirm"] ) === false ))
                {
                    $confirm                                    = false;
                }

                if ( !$confirm) { break; }

                $is_possible                                   = true;
                
                if ( ( array_key_exists( $type . "_confirm_not", $language_options ) ) && ( strpos ( $line, $language_options[$type . "_confirm_not"] ) !== false ) ) 
                {
                    $is_possible                               = false;
                }

                if ( !$is_possible) { break; }
                
                ( $type === "start" ) ?  array_push ( $file_structure, $language ) : array_pop ( $file_structure );

                $finded                                         = true;
                
                break;
            }
        }

       $condition                                             = ( $type === "start" ) ? true : ( end ( $file_structure ) === $this->default_language );

        if ( ( !$finded ) && ( $condition ) )
        {
            $add                                               = true;

            $ends                                              = array_column ( $this->languages, "end" );

            foreach ( $ends as $end )
            {
                if ( substr( $line, 0, strlen ( $end ) ) === $end  )
                {
                    $add                                       = false;
                    
                    break;
                }
            }

            if ( $add )
            {
                if ( $type === "start" )
                {
                    $word                                          = strtok ( $line, ' ');

                    if ( strpos ( $word, ">" ) !==false )
                    {
                        $check_first_html                          = trim( substr ( $word, 1, ( strpos( $word, ">" ) - 1 ) ) );
                    }
                    else{
                        $check_first_html                          = str_replace ( ["<", ">"], ["", ""], $word);
                    }

                    if ( $this->first_html === "" )
                    {
                        $this->first_html                          = $check_first_html;

                        $this->counter_first_html++;

                        array_push ( $file_structure, $this->default_language );
                    }
                    else if ( $check_first_html === $this->first_html )
                    {
                        $this->counter_first_html++;
                    }
                }
                else{
                    if ( substr( $line, 0, ( strlen ( $this->first_html ) +3 ) ) === "</" . $this->first_html . ">"  )
                    {
                        $this->counter_first_html --;
                    }

                    if ( $this->counter_first_html == 0 )
                    {
                        $this->first_html                              = "";

                        array_pop ( $file_structure );
                    }
                }
            }
        }

        return $file_structure;
    }
}