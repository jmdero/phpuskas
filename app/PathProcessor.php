<?php

namespace App;

class PathProcessor {
    private string  $path                = "";

    private string  $extension           = ""; 

    private array   $lines               = array (); 

    private array   $start_blancks       = array ();

    public function process_path ( string $path ): bool
    {
        $this->path                    = $path;

        $this->extension               = pathinfo($this->path, PATHINFO_EXTENSION);

        $processed                     = false;

        if ( ! file_exists ($path) ) { return $processed; }

        $file_content                   = file_get_contents ( $this->path, true );

        if ( ! in_array ( $this->extension, json_decode ( FILE_VALID_EXTENSIONS ) ) ){ return $processed; }
        
        $this->lines                   = explode ( PHP_EOL, $file_content );

        if ( count ( $this->lines ) == 0 ) { return $processed; }

        $this->process_lines();

        $this->set_new_file();

        return true;
    }

    private function process_lines ()
    {
        $new_lines                    = array ();

        $add_lines                    = array ();

        $php_eol                      = true;

        foreach ( $this->lines as $key_line => $line)
        {
            if ( $line === "" )
            {
                unset( $this->lines[$key_line] );

                continue;
            }

            $new_line                  = $this->line_spaces( $line, $key_line );

            extract ( $this->check_end_line ( $new_line, $key_line, $add_lines, $php_eol ) );

            $new_lines[]               = $new_line.PHP_EOL;

            if ( !empty ( $add_lines ) )
            {
                foreach ( $add_lines as $add )
                {
                    $new_lines[]       = $add;
                }

                $add_lines             = array ();
            }

            $php_eol                   = false;
        }

        $this->lines                   = $new_lines;
    }

    private function line_spaces (string $line, int $key_line ) : string
    {
        $line                          = ( substr( $line, -1 ) === " " ) ? substr( $line, 0, -1 ) : $line ;

        $characters                    = str_split( $line );

        $counter_back                  = 1;

        $start_text                    = false;
        
        $counter_start_blanck          = 0;

        foreach ( $characters as $key_character => $character )
        {
            if ( $character === " " )
            {
                if ( $start_text )
                {
                    $key_back                = ( $key_character - $counter_back );

                    if ( array_key_exists( $key_back, $characters ) and ( ( $characters[$key_back] == " " ) or ( $characters[$key_back] == "\t" ) ) )
                    {
                            $counter_back++;

                            unset ( $characters[$key_character] );
                    }
                    else{
                            $counter_back       = 1;
                    }
                } 
                else{
                    $counter_start_blanck ++;
                }
            }
            else
            {
                $start_text             = true;
            }
        }

        $characters                     = array_values ( $characters );

        $line                           = implode( "", $characters );

        if ( $counter_start_blanck > 0 )
        {
            $this->start_blancks[$key_line]      = $counter_start_blanck;
        }

        return $line;
    }

    private function check_end_line ( string $new_line, int $key_line, array $add_lines, bool $php_eol ) : array
    {
        if ( substr ( $new_line, -1) == "{" ){
                
            $new_line              = substr ( $new_line, 0, -1);

            $add_blanks            = '';

            $prev_key              = ( $key_line - 1 );
            
            if ( array_key_exists ( $prev_key, $this->start_blancks ) )
            {
                for ( $i = 0; $i < $this->start_blancks[$prev_key] ; $i ++ ) { 
                   
                    $add_blanks   .= ' ';
                }
            }

            $add_lines[]           = $add_blanks.'{';

            $php_eol               = false;
        }

        return compact ( 'new_line', 'add_lines', 'php_eol' );
    }
    
    private function set_new_file ()
    {
        $file_content                   = '';

        $not_eols                      = array ( '{', '}');

        foreach ( $this->lines as $line)
        {
            if ( ( strpos ( $line, '}' ) !== false ) and ( str_replace ( [' ', PHP_EOL],['',''], $line ) === '}' ) )
            {
                $line                   = str_replace ( [' ', PHP_EOL],['',''], $line );
            }
            
            $file_content               .= ( in_array ( $line, $not_eols ) ) ? '' : PHP_EOL;

            $file_content               .= $line;
        }

        $file                           = fopen ( $this->path . "_copy." . $this->extension, "w");

        fwrite( $file, $file_content );

        fclose( $file );
    }
}