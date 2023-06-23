<?php

namespace App;

class FinderFiles {
    private string  $directory;
    private array   $valid_extensions;

    public function __construct ( string $directory)
    {
        $this->directory                    = $directory;

        $this->valid_extensions             = json_decode ( FILE_VALID_EXTENSIONS );
    }
    public function getFiles ( array $files = [], string $directory = '' ) : array
    {
        $directory                          = ( $directory == '' ) ? $this->directory : $directory;    
        
        if ( is_dir ( $directory ) )
        {
            $content_folder                  = scandir ( $directory );

            $content_folder                  = array_diff( $content_folder, [ '.', '..' ] );

            foreach ( $content_folder as $content )
            {   
                if ( is_dir ( $directory.'/'.$content ) )
                {
                    $directory               .= '/'.$content;

                    $files                    = $this->getFiles ( $files, $directory );
                }
                else
                {
                    $extension               = pathinfo($content, PATHINFO_EXTENSION);

                    if ( in_array ( $extension, $this->valid_extensions ) )
                    {
                        array_push ( $files, $directory.'/'.$content );
                    }
                }
            }
        }
        return $files;
    }
}