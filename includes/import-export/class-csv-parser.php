<?php

/**
 * CSV Parser for import operations.
 */
class Club_Manager_CSV_Parser {
    
    /**
     * Parse a CSV or Excel file.
     * 
     * @param string $file_path Path to the file
     * @param string $mime_type File MIME type
     * @return array Parsed data with headers and rows
     */
    public function parse($file_path, $mime_type) {
        // For CSV files
        if (strpos($mime_type, 'csv') !== false || 
            strpos($mime_type, 'text/plain') !== false || 
            strpos($mime_type, 'text/csv') !== false ||
            strpos($mime_type, 'application/vnd.ms-excel') !== false) {
            return $this->parseCSV($file_path);
        }
        
        // For Excel files - would need PHPSpreadsheet library
        if (strpos($mime_type, 'spreadsheet') !== false || 
            strpos($mime_type, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') !== false) {
            throw new Exception('Excel files are not supported yet. Please use CSV format.');
        }
        
        // Try to detect by file extension if MIME type is not recognized
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            return $this->parseCSV($file_path);
        }
        
        throw new Exception('Unsupported file type');
    }
    
    /**
     * Parse CSV file with better handling of different formats.
     */
    private function parseCSV($file_path) {
        $data = array(
            'headers' => array(),
            'rows' => array()
        );
        
        // First, try to detect the encoding
        $content = file_get_contents($file_path);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            // Write back to temp file
            file_put_contents($file_path, $content);
        }
        
        // Remove BOM if present
        $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
            file_put_contents($file_path, $content);
        }
        
        // Open file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception('Could not open file');
        }
        
        // Detect delimiter
        $delimiter = $this->detectDelimiter($file_path);
        
        // Set locale for proper CSV parsing
        $original_locale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, 'en_US.UTF-8');
        
        try {
            // Read headers
            $headers = fgetcsv($handle, 0, $delimiter, '"', '"');
            if (!$headers) {
                throw new Exception('Could not read headers from file');
            }
            
            // Clean headers
            $headers = array_map(function($header) {
                // Remove any quotes, spaces, and special characters
                $header = str_replace(['"', "'", "\xEF\xBB\xBF"], '', $header);
                $header = trim($header);
                return $header;
            }, $headers);
            
            // Ensure headers are not empty
            $headers = array_filter($headers, function($header) {
                return !empty($header);
            });
            
            if (empty($headers)) {
                throw new Exception('No valid headers found in CSV file');
            }
            
            $data['headers'] = array_values($headers);
            
            // Log headers for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CSV Parser - Headers found: ' . json_encode($data['headers']));
                error_log('CSV Parser - Delimiter used: ' . ($delimiter === "\t" ? 'TAB' : "'{$delimiter}'"));
            }
            
            // Read rows
            $row_number = 0;
            while (($row = fgetcsv($handle, 0, $delimiter, '"', '"')) !== false) {
                $row_number++;
                
                // Skip completely empty rows
                $non_empty_values = array_filter($row, function($value) {
                    return $value !== '' && $value !== null;
                });
                
                if (empty($non_empty_values)) {
                    continue;
                }
                
                // Ensure row has same number of columns as headers
                $row = array_slice(array_pad($row, count($headers), ''), 0, count($headers));
                
                // Clean values
                $row = array_map(function($value) {
                    // Remove quotes and trim
                    $value = str_replace(['"', "'"], '', $value);
                    return trim($value);
                }, $row);
                
                // Log first few rows for debugging
                if (defined('WP_DEBUG') && WP_DEBUG && $row_number <= 3) {
                    error_log('CSV Parser - Row ' . $row_number . ': ' . json_encode($row));
                }
                
                $data['rows'][] = $row;
            }
            
        } finally {
            fclose($handle);
            setlocale(LC_ALL, $original_locale);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CSV Parser - Total rows parsed: ' . count($data['rows']));
        }
        
        if (empty($data['rows'])) {
            throw new Exception('No data rows found in CSV file');
        }
        
        return $data;
    }
    
    /**
     * Improved delimiter detection.
     */
    private function detectDelimiter($file_path) {
        $delimiters = array(',', ';', "\t", '|');
        $handle = fopen($file_path, 'r');
        
        if (!$handle) {
            return ','; // Default to comma
        }
        
        // Read first few lines for better detection
        $lines = array();
        for ($i = 0; $i < 5 && !feof($handle); $i++) {
            $line = fgets($handle);
            if ($line !== false) {
                $lines[] = $line;
            }
        }
        fclose($handle);
        
        if (empty($lines)) {
            return ','; // Default to comma
        }
        
        $delimiter_scores = array();
        
        foreach ($delimiters as $delimiter) {
            $scores = array();
            
            foreach ($lines as $line) {
                // Count occurrences in quotes vs outside quotes
                $in_quotes = false;
                $count = 0;
                $chars = str_split($line);
                
                for ($i = 0; $i < count($chars); $i++) {
                    if ($chars[$i] === '"' && ($i === 0 || $chars[$i-1] !== '\\')) {
                        $in_quotes = !$in_quotes;
                    } elseif (!$in_quotes && $chars[$i] === $delimiter) {
                        $count++;
                    }
                }
                
                $scores[] = $count;
            }
            
            // Check consistency
            if (count(array_unique($scores)) === 1 && $scores[0] > 0) {
                // All lines have same count, good indicator
                $delimiter_scores[$delimiter] = $scores[0] * 10; // High score for consistency
            } else {
                // Variable counts, less reliable
                $delimiter_scores[$delimiter] = array_sum($scores) / count($scores);
            }
        }
        
        if (empty($delimiter_scores) || max($delimiter_scores) === 0) {
            return ','; // Default to comma
        }
        
        // Get delimiter with highest score
        arsort($delimiter_scores);
        $detected = key($delimiter_scores);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CSV Parser - Detected delimiter: ' . ($detected === "\t" ? 'TAB' : "'{$detected}'") . ' (score: ' . $delimiter_scores[$detected] . ')');
        }
        
        return $detected;
    }
}