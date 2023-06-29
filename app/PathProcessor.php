<?php

namespace App;

class PathProcessor
{
    private string  $path                   = "";

    private string  $extension              = "";

    private array  $line_characters        = array ();
    
    private array   $lines                  = array (); 

    private array   $start_blancks          = array ();

    private array   $blanck_setters         = array ();

    public function process_path ( string $path ): bool
    {
        $this->path                         = $path;

        $this->extension                    = pathinfo($this->path, PATHINFO_EXTENSION);

        $processed                          = false;

        if ( ! file_exists ($path) )
        {
            return $processed;
        }

        $file_content                       = file_get_contents ( $this->path, true );

        if ( ! in_array ( $this->extension, json_decode ( FILE_VALID_EXTENSIONS ) ) )
        {
            return $processed;
        }

        $this->lines                        = explode ( PHP_EOL, $file_content );

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
        $new_lines                          = array ();

        $add_lines                          = array ();

        foreach ( $this->lines as $key_line => $line)
        {
            if ( $line === "" )
            {
                unset( $this->lines[$key_line] );

                continue;
            }
            $new_line                       = $this->clean_line_spaces( $line, $key_line );

            $new_line                       = $this->check_end_line ( $new_line, $key_line);

            $next_line                      = $key_line + 1 ;

            $open_type                      = substr ( str_replace ( [PHP_EOL," "],["",""], $new_line ), -1 );

            $close_type                     = "";

            if ( array_key_exists ( $next_line, $this->lines ) )
            {
                $close_type                 = substr (  str_replace ( [PHP_EOL," "],["",""], $this->lines[$next_line] ), -1 );
            }

            if ( ( array_key_exists ( $next_line, $this->lines ) )  and ( ( $close_type != "}" ) and ( $open_type != "{" ) ) )
            {
                $new_line                   .= PHP_EOL; 
            }

            $new_lines[]                    = $new_line.PHP_EOL;

            if ( !empty ( $add_lines ) )
            {
                foreach ( $add_lines as $add )
                {
                    $new_lines[]            = $add;
                }
                $add_lines                  = array ();
            }
        }
        $this->lines                        = $new_lines;
    }

    private function clean_line_spaces (string $line, int $key_line ) : string
    {
        $line                               = ( substr( $line, -1 ) === " " ) ? substr( $line, 0, -1 ) : $line ;

        $this->line_characters              = str_split( $line );

        $counter_back                       = 1;

        $start_text                         = false;
        
        $counter_start_blanck               = 0;

        foreach ( $this->line_characters as $key_character => $character )
        {
            if ( $character === " " )
            {
                if ( $start_text )
                {
                    $key_back               = ( $key_character - $counter_back );

                    if ( array_key_exists( $key_back, $this->line_characters ) and ( ( $this->line_characters[$key_back] == " " ) or ( $this->line_characters[$key_back] == "\t" ) ) )
                    {
                            $counter_back++;

                            unset ( $this->line_characters[$key_character] );
                    }
                    else{
                            $counter_back   = 1;
                    }
                } 
                else{
                    $counter_start_blanck ++;
                }
            }
            else
            {
                $start_text                 = true;
            }
        }

        $this->line_characters              = array_values ( $this->line_characters );

        $line                               = implode( "", $this->line_characters );

        if ( $counter_start_blanck > 0 )
        {
            $this->start_blancks[$key_line] = $counter_start_blanck;
        }
        return $line;
    }

    private function check_end_line ( string $new_line, int $key_line) : string
    {
        if ( substr ( $new_line, -1) == "{" ){

            $add_blanks                     = '';

            $prev_key                       = ( $key_line - 1 );
            
            if ( array_key_exists ( $prev_key, $this->start_blancks ) )
            {
                for ( $i = 0; $i < $this->start_blancks[$prev_key] ; $i ++ ) { 
                   
                    $add_blanks             .= ' ';
                }
            }
            $new_line                       = substr ( $new_line, 0, -1).PHP_EOL.$add_blanks.substr ( $new_line, -1);
        }
        return $new_line;
    }
    
    private function set_new_file ()
    {
        $file_content                       = '';

        foreach ( $this->lines as $line)
        {
            $file_content                   .= $line;
        }

        $file                               = fopen ( $this->path . "_copy." . $this->extension, "w");

        fwrite( $file, $file_content );

        fclose( $file );
    }
}