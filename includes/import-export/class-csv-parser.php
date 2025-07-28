<?php

/**
 * CSV Parser for import operations.
 */
class Club_Manager_CSV_Parser {
    
    /**
     * Parse a CSV file.
     * Note: Only CSV format is supported.
     * 
     * @param string $file_path Path to the file
     * @param string $mime_type File MIME type (optional, can be empty)
     * @param string $original_filename Original filename (optional, for extension checking)
     * @return array Parsed data with headers and rows
     */
    public function parse($file_path, $mime_type = '', $original_filename = '') {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CSV Parser - Starting parse for file: ' . basename($file_path));
            error_log('CSV Parser - MIME type: ' . $mime_type);
            error_log('CSV Parser - Original filename: ' . $original_filename);
        }
        
        // For temporary files, we need to check the original filename
        if (!empty($original_filename)) {
            $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        } else {
            // Fallback to checking the file path itself
            $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CSV Parser - Detected extension: ' . $extension);
        }
        
        // Check for CSV extension
        if ($extension === 'csv') {
            return $this->parseCSV($file_path);
        }
        
        // Check for unsupported extensions
        if (in_array($extension, array('xls', 'xlsx', 'xlsm'))) {
            throw new Exception('Only CSV files are supported. Please save your file as CSV format using any spreadsheet application.');
        }
        
        // If no extension found and mime type suggests CSV, allow it
        if (empty($extension) && !empty($mime_type)) {
            $csv_mime_types = array('text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values');
            foreach ($csv_mime_types as $csv_type) {
                if (stripos($mime_type, $csv_type) !== false) {
                    return $this->parseCSV($file_path);
                }
            }
        }
        
        // If we get here, it's an unknown file type
        throw new Exception('Unsupported file type. Please upload a CSV file.');
    }
    
    /**
     * Parse CSV file with robust handling.
     */
    private function parseCSV($file_path) {
        $data = array(
            'headers' => array(),
            'rows' => array()
        );
        
        // Check if file exists
        if (!file_exists($file_path)) {
            throw new Exception('File not found: ' . $file_path);
        }
        
        // Read file content to check encoding and BOM
        $content = file_get_contents($file_path);
        if ($content === false) {
            throw new Exception('Could not read file content');
        }
        
        // Remove BOM if present
        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
            file_put_contents($file_path, $content);
        }
        
        // Detect line endings and normalize to \n
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        file_put_contents($file_path, $content);
        
        // Detect delimiter
        $delimiter = $this->detectDelimiter($file_path);
        
        // Open file for reading
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception('Could not open file for reading');
        }
        
        try {
            // Read headers
            $headers = fgetcsv($handle, 0, $delimiter);
            if (!$headers) {
                throw new Exception('Could not read headers from file');
            }
            
            // Clean headers - remove quotes, spaces, BOM
            $headers = array_map(function($header) {
                // Remove BOM
                $header = str_replace("\xEF\xBB\xBF", '', $header);
                // Remove quotes
                $header = trim($header, '"\'');
                // Trim spaces
                $header = trim($header);
                // Convert to lowercase for consistency
                $header = strtolower($header);
                // Replace spaces with underscores
                $header = str_replace(' ', '_', $header);
                return $header;
            }, $headers);
            
            // Remove empty headers
            $headers = array_filter($headers, function($h) { return !empty($h); });
            
            if (empty($headers)) {
                throw new Exception('No valid headers found in CSV file');
            }
            
            $data['headers'] = array_values($headers);
            $header_count = count($data['headers']);
            
            // Debug headers
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CSV Parser - Headers: ' . json_encode($data['headers']));
                error_log('CSV Parser - Delimiter: ' . ($delimiter === "\t" ? 'TAB' : $delimiter));
            }
            
            // Read data rows
            $row_number = 0;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $row_number++;
                
                // Skip empty rows
                if (count(array_filter($row, function($v) { return $v !== ''; })) === 0) {
                    continue;
                }
                
                // Ensure row has correct number of columns
                if (count($row) < $header_count) {
                    $row = array_pad($row, $header_count, '');
                } elseif (count($row) > $header_count) {
                    $row = array_slice($row, 0, $header_count);
                }
                
                // Clean values
                $row = array_map(function($value) {
                    // Remove quotes
                    $value = trim($value, '"\'');
                    // Trim spaces
                    return trim($value);
                }, $row);
                
                $data['rows'][] = $row;
                
                // Debug first few rows
                if (defined('WP_DEBUG') && WP_DEBUG && $row_number <= 3) {
                    error_log('CSV Parser - Row ' . $row_number . ': ' . json_encode($row));
                }
            }
            
        } finally {
            fclose($handle);
        }
        
        if (empty($data['rows'])) {
            throw new Exception('No data rows found in CSV file');
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CSV Parser - Total rows parsed: ' . count($data['rows']));
        }
        
        return $data;
    }
    
    /**
     * Detect CSV delimiter.
     */
    private function detectDelimiter($file_path) {
        $delimiters = array(',', ';', "\t", '|');
        $handle = fopen($file_path, 'r');
        
        if (!$handle) {
            return ',';
        }
        
        // Read first 5 lines
        $lines = array();
        for ($i = 0; $i < 5 && !feof($handle); $i++) {
            $line = fgets($handle);
            if ($line !== false) {
                $lines[] = $line;
            }
        }
        fclose($handle);
        
        if (empty($lines)) {
            return ',';
        }
        
        $scores = array();
        
        foreach ($delimiters as $delimiter) {
            $counts = array();
            
            foreach ($lines as $line) {
                $count = substr_count($line, $delimiter);
                if ($count > 0) {
                    $counts[] = $count;
                }
            }
            
            if (empty($counts)) {
                $scores[$delimiter] = 0;
            } else {
                // Check consistency - all lines should have same count
                $unique_counts = array_unique($counts);
                if (count($unique_counts) === 1) {
                    // Consistent count across lines = good delimiter
                    $scores[$delimiter] = $counts[0] * 100; // High score for consistency
                } else {
                    // Inconsistent = lower score
                    $scores[$delimiter] = array_sum($counts) / count($counts);
                }
            }
        }
        
        if (empty($scores) || max($scores) === 0) {
            return ',';
        }
        
        arsort($scores);
        return key($scores);
    }
}