<?php

/**
 * Parse CSV and Excel files for import.
 */
class Club_Manager_CSV_Parser {
    
    /**
     * Parse file and return data.
     */
    public function parse($file_path, $mime_type) {
        // Determine file type
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if ($file_extension === 'xlsx' || $file_extension === 'xls' || 
            strpos($mime_type, 'excel') !== false || 
            strpos($mime_type, 'spreadsheet') !== false) {
            return $this->parseExcel($file_path);
        } else {
            return $this->parseCSV($file_path);
        }
    }
    
    /**
     * Parse CSV file - FIXED: Better encoding and delimiter detection.
     */
    private function parseCSV($file_path) {
        $data = array(
            'headers' => array(),
            'rows' => array()
        );
        
        // Detect encoding
        $content = file_get_contents($file_path);
        $encoding = $this->detectEncoding($content);
        
        // Convert to UTF-8 if needed
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $temp_file = $file_path . '.utf8';
            file_put_contents($temp_file, $content);
            $file_path = $temp_file;
        }
        
        // Open file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception('Could not open file');
        }
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        // Detect delimiter
        $delimiter = $this->detectDelimiter($file_path);
        
        // Read headers
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            throw new Exception('No headers found in file');
        }
        
        // Clean headers
        $data['headers'] = array_map(array($this, 'cleanHeader'), $headers);
        
        // Read data rows
        $row_number = 1;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Ensure row has same number of columns as headers
            while (count($row) < count($data['headers'])) {
                $row[] = '';
            }
            
            // Trim values
            $row = array_map('trim', $row);
            
            $data['rows'][] = $row;
            
            // Limit to prevent memory issues
            if (++$row_number > 10000) {
                break;
            }
        }
        
        fclose($handle);
        
        // Clean up temp file
        if (isset($temp_file) && file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        return $data;
    }
    
    /**
     * Parse Excel file - FIXED: Better PhpSpreadsheet support check.
     */
    private function parseExcel($file_path) {
        // Check if PhpSpreadsheet is available
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
                $reader->setReadDataOnly(true);
                $reader->setReadEmptyCells(false);
                
                $spreadsheet = $reader->load($file_path);
                $worksheet = $spreadsheet->getActiveSheet();
                
                $data = array(
                    'headers' => array(),
                    'rows' => array()
                );
                
                $rows = $worksheet->toArray();
                
                if (!empty($rows)) {
                    // First row is headers
                    $data['headers'] = array_map(array($this, 'cleanHeader'), array_shift($rows));
                    
                    // Rest are data rows
                    foreach ($rows as $row) {
                        // Skip empty rows
                        if (!empty(array_filter($row))) {
                            $data['rows'][] = array_map('trim', $row);
                        }
                        
                        // Limit rows
                        if (count($data['rows']) >= 10000) {
                            break;
                        }
                    }
                }
                
                return $data;
                
            } catch (Exception $e) {
                Club_Manager_Logger::log('PhpSpreadsheet error: ' . $e->getMessage(), 'error');
                // Fall back to CSV parsing
                return $this->parseCSV($file_path);
            }
        }
        
        // Try alternative methods if PhpSpreadsheet not available
        
        // Check if we can use COM (Windows only)
        if (class_exists('COM')) {
            try {
                return $this->parseExcelWithCOM($file_path);
            } catch (Exception $e) {
                Club_Manager_Logger::log('COM Excel parsing failed: ' . $e->getMessage(), 'error');
            }
        }
        
        // Fall back to treating as CSV
        Club_Manager_Logger::log('No Excel parser available, treating as CSV', 'warning');
        return $this->parseCSV($file_path);
    }
    
    /**
     * Parse Excel with COM (Windows only).
     */
    private function parseExcelWithCOM($file_path) {
        try {
            $excel = new COM("Excel.Application") or die("Unable to instantiate Excel");
            $excel->Visible = false;
            $excel->DisplayAlerts = false;
            
            $workbook = $excel->Workbooks->Open(realpath($file_path));
            $worksheet = $workbook->Worksheets(1);
            
            $data = array(
                'headers' => array(),
                'rows' => array()
            );
            
            // Get used range
            $range = $worksheet->UsedRange;
            $rows = $range->Rows->Count;
            $cols = $range->Columns->Count;
            
            // Read headers
            for ($col = 1; $col <= $cols; $col++) {
                $value = $worksheet->Cells(1, $col)->Value;
                if ($value !== null) {
                    $data['headers'][] = $this->cleanHeader($value);
                }
            }
            
            // Read data
            for ($row = 2; $row <= $rows && $row <= 10001; $row++) {
                $row_data = array();
                $has_data = false;
                
                for ($col = 1; $col <= count($data['headers']); $col++) {
                    $value = $worksheet->Cells($row, $col)->Value;
                    $row_data[] = $value !== null ? trim($value) : '';
                    if ($value !== null && $value !== '') {
                        $has_data = true;
                    }
                }
                
                if ($has_data) {
                    $data['rows'][] = $row_data;
                }
            }
            
            $workbook->Close(false);
            $excel->Quit();
            
            return $data;
            
        } catch (Exception $e) {
            if (isset($excel)) {
                $excel->Quit();
            }
            throw new Exception('Failed to parse Excel file: ' . $e->getMessage());
        }
    }
    
    /**
     * Detect file encoding - FIXED: Better BOM detection.
     */
    private function detectEncoding($content) {
        // Check for BOM
        $first_bytes = substr($content, 0, 3);
        
        // UTF-8 BOM
        if ($first_bytes === "\xEF\xBB\xBF") {
            return 'UTF-8';
        }
        
        // UTF-16 BOMs
        $first_two = substr($content, 0, 2);
        if ($first_two === "\xFF\xFE") {
            return 'UTF-16LE';
        } elseif ($first_two === "\xFE\xFF") {
            return 'UTF-16BE';
        }
        
        // Try to detect encoding with mb_detect_encoding
        $encodings = array('UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII');
        
        // Use strict mode for better detection
        $detected = mb_detect_encoding($content, $encodings, true);
        if ($detected) {
            return $detected;
        }
        
        // Additional check for each encoding
        foreach ($encodings as $encoding) {
            if (mb_check_encoding($content, $encoding)) {
                return $encoding;
            }
        }
        
        // Default to ISO-8859-1 if nothing detected
        return 'ISO-8859-1';
    }
    
    /**
     * Detect CSV delimiter - FIXED: Excel-aware delimiter detection.
     */
    private function detectDelimiter($file_path) {
        // Common delimiters - semicolon first for Excel exports in certain locales
        $delimiters = array(';', ',', "\t", '|');
        $results = array();
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return ',';
        }
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        // Read first few lines
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
        
        // Count occurrences and consistency of each delimiter
        foreach ($delimiters as $delimiter) {
            $counts = array();
            $consistency = 0;
            
            foreach ($lines as $line) {
                $count = substr_count($line, $delimiter);
                $counts[] = $count;
            }
            
            // Check if counts are consistent across lines
            if (!empty($counts)) {
                $avg = array_sum($counts) / count($counts);
                if ($avg > 0) {
                    // Calculate standard deviation
                    $variance = 0;
                    foreach ($counts as $count) {
                        $variance += pow($count - $avg, 2);
                    }
                    $variance = $variance / count($counts);
                    $std_dev = sqrt($variance);
                    
                    // Lower std dev means more consistent
                    $consistency = $avg / (1 + $std_dev);
                }
            }
            
            $results[$delimiter] = $consistency;
        }
        
        // Return delimiter with highest consistency score
        arsort($results);
        $delimiter = key($results);
        
        return $delimiter ?: ',';
    }
    
    /**
     * Clean header value - FIXED: Better whitespace handling.
     */
    private function cleanHeader($header) {
        // Remove BOM
        $header = str_replace("\xEF\xBB\xBF", '', $header);
        
        // Convert to string if needed
        $header = (string)$header;
        
        // Trim all types of whitespace
        $header = trim($header, " \t\n\r\0\x0B");
        
        // Remove quotes
        $header = trim($header, '"\'');
        
        // Normalize internal whitespace
        $header = preg_replace('/\s+/', ' ', $header);
        
        // Remove any non-printable characters
        $header = preg_replace('/[\x00-\x1F\x7F]/', '', $header);
        
        // Convert to lowercase for consistency
        $header = strtolower($header);
        
        // Replace spaces with underscores for field matching
        $header = str_replace(' ', '_', $header);
        
        return $header;
    }
}