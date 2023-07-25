<?php
namespace App;

require_once "language_checker.php";

use app\language_checker;

class path_processor
{
    private string  $path                               = "";

    private string  $extension                          = "";

    private array  $line_characters                     = array ();
    
    private array   $lines                              = array (); 

    private array   $start_blancks                      = array ();

    private array   $blanck_setters                     = array ();

    private array   $file_structure                      = array ();

    public function process_path ( string $path ): bool
    {
        $this->path                                     = $path;

        $this->extension                                = pathinfo ( $this->path, PATHINFO_EXTENSION );

        $processed                                      = false;

        if ( ! file_exists ( $path ) )
        {
            return $processed;
        }

        $file_content                                    = file_get_contents ( $this->path, true );

        if ( ! in_array ( $this->extension, json_decode ( FILE_VALID_EXTENSIONS ) ) )
        {
            return $processed;
        }

        $this->lines                                    = explode ( PHP_EOL, $file_content );

        if ( count ( $this->lines ) == 0 )
        {
            return $processed;
        }

        $this->process_lines();

        $this->set_new_file();

        return true;
    }

    private function process_lines ()
    {
        $new_lines                                      = array ();

        $add_lines                                      = array ();

        $language_checker                               = new language_checker ();

        $space_makers                                   = array ( "(", ")", ".", "=", ":", ",", "?" );

        $not_space_makers                               = array ( " ", ";", "=", "<", ">", "(", ")", ":", ".", "-", "+" );

        $exceptions_spaces                              = array
        (
            ")"                                         => array ( ":", "=", ".", ")" ),
        );

        $special_add_spaces                             = array ( "=>"  );

        $is_conditional                                 = false;

        $is_string                                      = array ( "simple" => false, "double" => false );

        $max_equal_position                             = 0;

        $equal_lines                                    = array ();

        $equals_counter                                 = 0;

        $eol_counter                                    = 0;

        foreach ( $this->lines as $key_line => $line )
        {
            if ( ( $line === " " ) && ( $line === "\s" )  )
            {
                unset( $this->lines[$key_line] );

                continue;
            }
            
            $new_line                                   = $this->clean_line_spaces ( $line, $key_line );

            $characters                                 = get_characters ( $new_line );

            if ( count ( array_unique ( $characters ) ) == 0 )
            {
                unset( $this->lines[$key_line] );

                continue;
            }

            $changed_structure                          = false;

            if ( count ( $characters ) > 0 )
            {   
                $total_spaces                           = 0;

                $equals_counter                         = 0;

                foreach ( $characters as $key_character => $character )
                {
                    if ( ( $character === '"' ) || ( $character === "'" ) )
                    {
                        $type_string                    = ( $character === "'" ) ? "simple" : "double";

                        $is_string[$type_string]        = !$is_string[$type_string];
                    }

                    if ( ( $character === '(' ) || ( $character === ")" ) )
                    {
                        $is_conditional                 = !$is_conditional;
                    }

                    $is_not_string                      = ( !in_array ( "true", array_values ( $is_string ) ) );

                    if ( ( !$is_conditional ) && ( $is_not_string )  && ( ( $character === "<" ) || ( $character === "?" ) ) )
                    {
                        $this->file_structure            = $language_checker->check_structure ( substr($line,$key_character), $this->file_structure );

                        $changed_structure              = true;
                    }
                    else if ( ( !$is_conditional ) && ( $is_not_string ) && ( $character === "=" ) )
                    {
                        $equals_counter++;
                    }

                    $special_position                   = false;

                    $special_type                       = '';

                    if ( count ( $special_add_spaces ) > 0 )
                    {
                        foreach ( $special_add_spaces as $special )
                        {
                            $special_position           = strpos ( $special, $character );

                            if ( is_int ( $special_position ) )
                            {
                                $matches                = 0;

                                if ( $special_position === 0)
                                {
                                    for ( $i = 0; $i < strlen ( $special ) ; $i ++ )
                                    {
                                        if ( substr ( $new_line, $key_character + $i + $total_spaces, 1 ) === $special[$i] )
                                        {
                                            $matches ++;
                                        }
                                        else{
                                            break;
                                        }
                                    }
                                }

                                if ( $special_position === ( strlen ( $special ) - 1 ) )
                                {   
                                    $minus                = 0;

                                    for ( $i = ( strlen ( $special ) - 1 ) ; $i >= 0 ; $i -- )
                                    {   
                                        if ( substr ( $new_line,( ( $key_character + $total_spaces ) - $minus ), 1 ) === $special[$i] )
                                        {
                                            $matches ++;
                                        }
                                        else{
                                            break;
                                        }
                                        $minus++;
                                    }
                                }

                                if ( $matches === strlen ( $special ) )
                                {
                                    $special_type        = ( $special_position == ( strlen ( $special ) - 1 ) ) ? "end": "start";
                                    break;
                                }
                            }   
                        }
                    }

                    if ( ( $is_not_string ) && ( ( in_array ( $character, $space_makers ) ) || ( $special_type !== "" ) ) )
                    {
                        $change_line                    = substr ( $new_line, 0 , ( $key_character + $total_spaces ) );

                        $spaces_counter                 = 0;

                        $pass                           = ( $character !== "," ) && ( array_key_exists ( ( $key_character - 1 ), $characters ) ) && ( !in_array ( $characters[( $key_character - 1 )], $not_space_makers ) );

                        $pass                           = ( ( !$pass ) && ( $special_type == "start" ) ) ? true : $pass;

                        if ( $pass )
                        {
                            $change_line                .= " ";

                            $spaces_counter++;
                        }

                        $change_line                    .= $character;

                        $next_key                       = ( $key_character + 1 );

                        $pass                           = ( ( array_key_exists ( $next_key, $characters ) ) && ( !in_array ( $characters[$next_key], $not_space_makers ) ) );

                        $pass                           = ( !$pass ) ? ( ( array_key_exists ( $character, $exceptions_spaces ) ) && ( array_key_exists ( $next_key, $exceptions_spaces ) ) && ( in_array ( $characters[$next_key], $exceptions_spaces[$character] ) ) ) : $pass;

                        $pass                           =  ( ( $pass ) && ( $character === "?" ) &&  ( substr ( $new_line, $next_key , 3 ) === "php" ) ) ? false : $pass;

                        $pass                           = ( ( !$pass ) && ( $special_type == "end" ) ) ? true : $pass;
                        
                        if ( $pass )
                        {
                            $change_line                .= " ";

                            $spaces_counter++;
                        }

                        $new_line                       = $change_line.substr ( $new_line, ( strlen ( $change_line ) - $spaces_counter ) ); 

                        $total_spaces                   += $spaces_counter;
                    }
                }
            }

            $new_line                                   = $this->check_end_line ( $new_line, $key_line );

            $next_line                                  = $key_line + 1 ;

            $open_type                                  = substr ( str_replace ( [ PHP_EOL, " " ], [ "", "" ], $new_line ), -1 );

            $close_type                                 = "";

            $add_line                                   = false;            

            if ( array_key_exists ( $next_line, $this->lines ) )
            {
                $close_type                             = substr (  str_replace ( [ PHP_EOL, " " ], [ "", "" ], $this->lines[$next_line] ), -1 );
            }

            if ( ( array_key_exists ( $next_line, $this->lines ) )  and ( ( $close_type != "}" ) and ( $open_type != "{" ) ) )
            {
               $new_lines[]                              = "".PHP_EOL;
            }

            if ( ( $equals_counter === 1 ) && ( !$changed_structure ) )
            {
                $equal_lines[]                          = $new_line;

                $max_equal_position                     = ( strpos ( $new_line, "=" ) > $max_equal_position ) ? strpos ( $new_line, "=" ) : $max_equal_position;
            }

            $new_lines[]                                = $new_line;

            if ( ( array_key_exists ( ( $key_line + 1 ), $this->lines ) ) && ( $this->lines[( $key_line + 1 )] !== PHP_EOL ) )
            {
                $new_lines[]                                = PHP_EOL;
            }

            if ( !empty ( $add_lines ) )
            {
                foreach ( $add_lines as $add )
                {
                    $new_lines[]                        = $add;
                }
                $add_lines                              = array ();
            }
        }
        
        $this->lines                                    = $new_lines;

        if ( ( $max_equal_position > 0 ) && ( count ( $equal_lines ) > 0 ) )
        {   
            foreach ( $this->lines as $key_line => $line )
            {
                if ( in_array ( $line, $equal_lines ) )
                {
                    $equal_position                     = strpos ( $line, "=" );

                    if ( ( $line[( $equal_position - 1 )] == "." ) || ( $line[( $equal_position - 1 )] == "+" ) )
                    {
                        $equal_position --;
                    }

                    $new_line                           = substr ( $line, 0, $equal_position );

                    $max_line                           = ( $max_equal_position - strlen ( $new_line ) );
                                        
                    for ( $i = 0; $i < $max_line; $i++ )
                    { 
                        $new_line                       .= " ";
                    }
                    $new_line                           .= substr ( $line, $equal_position, strlen ( $line ) );

                    $this->lines[$key_line]              = $new_line;
                }
            }
        }
    }

    private function clean_line_spaces ( string $line, int $key_line ) : string
    {
        $this->line_characters                          = get_characters ( $line );

        $counter_back                                   = 1;

        $start_text                                     = false;
        
        $counter_start_blanck                           = 0;

        foreach ( $this->line_characters as $key_character => $character )
        {
            if ( $character === " " )
            {
                if ( $start_text )
                {
                    $key_back                           = ( $key_character - $counter_back );

                    if ( array_key_exists( $key_back, $this->line_characters ) and ( ( $this->line_characters[$key_back] == " " ) or ( $this->line_characters[$key_back] == "\t" ) ) )
                    {
                            $counter_back++;

                            unset ( $this->line_characters[$key_character] );
                    }
                    else{
                            $counter_back               = 1;
                    }
                } 
                else{
                    $counter_start_blanck ++;
                }
            }
            else
            {
                $start_text                             = true;
            }
        }

        $this->line_characters                          = array_values ( $this->line_characters );

        $line                                           = implode( "", $this->line_characters );

        if ( $counter_start_blanck > 0 )
        {
            $this->start_blancks[$key_line]             = $counter_start_blanck;
        }
        return $line;
    }
    
    private function check_end_line ( string $new_line, int $key_line ) : string
    {
        if ( substr ( $new_line, -1) == "{" ){

            $add_blanks                                 = '';

            $prev_key                                   = ( $key_line - 1 );
            
            if ( array_key_exists ( $prev_key, $this->start_blancks ) )
            {
                for ( $i = 0; $i < $this->start_blancks[$prev_key] ; $i ++ )
                { 
                   
                    $add_blanks                         .= ' ';
                }
            }

            $new_line                                   = substr ( $new_line, 0, -1) . PHP_EOL . $add_blanks . substr ( $new_line, -1 );
        }
        return $new_line;
    }
    
    private function set_new_file ()
    {
        $file_content                                   = '';

        foreach ( $this->lines as $line )
        {
            $file_content                               .= $line;
        }

        $file                                           = fopen ( $this->path . "_copy." . $this->extension, "w" );

        fwrite( $file, $file_content );

        fclose( $file );
    }
}