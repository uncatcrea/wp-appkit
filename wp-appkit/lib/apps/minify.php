<?php

class WpakMinify {
    
    protected $jsqueeze = null;
    
    public function __construct() {
        require_once( dirname( __FILE__ ) . '/../../vendor/minification/js/JSqueeze.php' );
        require_once( dirname( __FILE__ ) . '/../../vendor/minification/css/minify.php' );
        $this->jsqueeze = new Patchwork\JSqueeze();
    }
    
    public function minify( $file_extension, $content_to_minify ) {
        $minified = $content_to_minify;
        
        $method = 'minify_'. $file_extension;
        if ( method_exists( $this, $method ) ) {
            $minified = $this->{$method}( $content_to_minify );
        }
        
        return $minified;
    }
    
    public function minify_js( $content_to_minify ) {
        
        $minified_content = $this->jsqueeze->squeeze(
            $content_to_minify,
            true,   // singleLine
            false,   // keepImportantComments
            false   // specialVarRx
        );
        
        return $minified_content;
    }
 
    public function minify_css( $content_to_minify ) {
	
        $minified_content = Minify_CSS_Compressor::process( $content_to_minify );

        return $minified_content;
    }
}

