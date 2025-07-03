<?php

/**
 * CSV Parser for import operations.
 */
class Club_Manager_CSV_Parser {
    
    /**
     * Parse a CSV or Excel file.
     * * @param string $file_path Path to the file
     * @param string $mime_type File MIME type
     * @return array Parsed data with headers and rows
     */
    public function parse($file_path, $mime_type) {
        // For CSV files
        if (strpos($mime_type, 'csv') !== false || strpos($mime_type, 'text/plain') !== false) {
            return $this->parseCSV($file_path);
        }
        
        // For Excel files - would need PHPSpreadsheet library
        if (strpos($mime_type, 'spreadsheet') !== false || strpos($mime_type, 'excel') !== false) {
            // For now, just return error - you'd need to install PHPSpreadsheet
            throw new Exception('Excel files are not supported yet. Please use CSV format.');
        }
        
        throw new Exception('Unsupported file type');
    }
    
    /**
     * Parse CSV file.
     */
    private function parseCSV($file_path) {
        $data = [
            'headers' => [],
            'rows'    => [],
        ];

        // Open the file
        if ( ( $handle = fopen( $file_path, 'r' ) ) === false ) {
            throw new Exception( 'Could not open file' );
        }

        // Detect the delimiter
        $delimiter = $this->detectDelimiter( $file_path );

        // Read headers
        $headers = fgetcsv( $handle, 0, $delimiter );
        if ( $headers === false ) {
            fclose( $handle );
            throw new Exception( 'Could not read headers from file' );
        }

        // Clean headers
        $data['headers'] = array_map( function ( $header ) {
            // Remove BOM
            $header = str_replace( "\xEF\xBB\xBF", '', $header );
            return trim( $header );
        }, $headers );

        // Read rows
        while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
            // Skip empty rows
            if ( empty( array_filter( $row ) ) ) {
                continue;
            }
            // If a row is parsed as a single column, try to parse it again
            if (count($row) === 1 && is_string($row[0])) {
                $sub_row = str_getcsv($row[0], $delimiter);
                if(count($sub_row) > 1){
                     $row = $sub_row;
                }
            }

            $data['rows'][] = array_map( 'trim', $row );
        }

        fclose( $handle );

        return $data;
    }
    
    /**
     * Detect CSV delimiter.
     */
    private function detectDelimiter($file_path) {
        $delimiters = array(',', ';', "\t", '|');
        $handle = fopen($file_path, 'r');
        $first_line = fgets($handle);
        fclose($handle);
        
        $delimiter_counts = array();
        foreach ($delimiters as $delimiter) {
            $delimiter_counts[$delimiter] = substr_count($first_line, $delimiter);
        }
        
        // Return delimiter with highest count
        arsort($delimiter_counts);
        reset($delimiter_counts);
        
        return key($delimiter_counts);
    }
}