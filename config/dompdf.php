<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DOMPDF Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the DOMPDF options that will be used by
    | the dompdf package. These options control the behavior of the PDF
    | rendering process.
    |
    */

    'show_warnings' => false,   // Set to true to enable warnings from dompdf

    'public_path' => null,  // Override the public path if needed

    'convert_entities' => true, // Set to false if you want to handle € and £ symbols differently

    'options' => [
        'font_dir' => storage_path('fonts'),  // Font directory
        'font_cache' => storage_path('fonts'),  // Font cache directory
        'temp_dir' => sys_get_temp_dir(),  // Temporary directory
        'chroot' => realpath(base_path()),  // Base path to prevent accessing other files
        'allowed_protocols' => [
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        // Enable/Disable PHP (be cautious about enabling it, especially with untrusted input)
        'is_php_enabled' => true,

        // Enable HTML5 parser (this is important for handling modern HTML)
        'is_html5_parser_enabled' => true,

        // Enable or disable font subsetting (increases file size but can be necessary for non-standard fonts)
        'is_font_subsetting_enabled' => true,

        // Additional settings
        'pdf_backend' => 'CPDF',  // Use CPDF as backend
        'default_media_type' => 'screen',  // Default media type
        'default_paper_size' => 'a4',  // Default paper size (A4)
        'default_paper_orientation' => 'portrait',  // Default orientation (portrait or landscape)
        'default_font' => 'serif',  // Default font family
        'dpi' => 96,  // Image DPI setting
        'enable_php' => false,  // Whether to enable embedded PHP (use cautiously)
        'enable_javascript' => true,  // Enable inline JavaScript (PDF-based)
        'enable_remote' => false,  // Allow remote resources (use cautiously)
        'font_height_ratio' => 1.1,  // Font height ratio
        'enable_html5_parser' => true,  // Enable HTML5 parser (deprecated in dompdf 2.x)
    ],

];
