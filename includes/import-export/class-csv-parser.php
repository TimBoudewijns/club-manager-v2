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
        if (strpos($mime_type, 'excel') !== false || strpos($mime_type, 'spreadsheet') !== false) {
            return $this->parseExcel($file_path);
        } else {
            return $this->parseCSV($file_path);
        }
    }
    
    /**
     * Parse CSV file.
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
            file_put_contents($file_path . '.utf8', $content);
            $file_path = $file_path . '.utf8';
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
        if (substr($file_path, -5) === '.utf8') {
            unlink($file_path);
        }
        
        return $data;
    }
    
    /**
     * Parse Excel file.
     */
    private function parseExcel($file_path) {
        // In production, you would use PhpSpreadsheet here
        // For now, we'll try to convert Excel to CSV using available methods
        
        // Check if we can use COM (Windows only)
        if (class_exists('COM')) {
            return $this->parseExcelWithCOM($file_path);
        }
        
        // Fall back to treating as CSV
        return $this->parseCSV($file_path);
        
        /* PhpSpreadsheet implementation:
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
            $data['headers'] = array_map(array($this, 'cleanHeader'), array_shift($rows));
            
            foreach ($rows as $row) {
                if (!empty(array_filter($row))) {
                    $data['rows'][] = array_map('trim', $row);
                }
            }
        }
        
        return $data;
        */
    }
    
    /**
     * Parse Excel with COM (Windows only).
     */
    private function parseExcelWithCOM($file_path) {
        try {
            $excel = new COM("Excel.Application") or die("Unable to instantiate Excel");
            $excel->Visible = false;
            $excel->DisplayAlerts = false;
            
            $workbook = $excel->Workbooks->Open($file_path);
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
            for ($row = 2; $row <= $rows && $row <= 10000; $row++) {
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
     * Detect file encoding.
     */
    private function detectEncoding($content) {
        // Check for BOM
        $bom = substr($content, 0, 3);
        if ($bom === "\xEF\xBB\xBF") {
            return 'UTF-8';
        } elseif ($bom === "\xFF\xFE" || $bom === "\xFE\xFF") {
            return 'UTF-16';
        }
        
        // Try to detect encoding
        $encodings = array('UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII');
        
        foreach ($encodings as $encoding) {
            if (mb_check_encoding($content, $encoding)) {
                return $encoding;
            }
        }
        
        // Use mb_detect_encoding as fallback
        return mb_detect_encoding($content, $encodings, true);
    }
    
    /**
     * Detect CSV delimiter.
     */
    private function detectDelimiter($file_path) {
        $delimiters = array(',', ';', "\t", '|');
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
        
        // Count occurrences of each delimiter
        foreach ($delimiters as $delimiter) {
            $count = 0;
            foreach ($lines as $line) {
                $count += substr_count($line, $delimiter);
            }
            $results[$delimiter] = $count;
        }
        
        // Return delimiter with highest count
        arsort($results);
        $delimiter = key($results);
        
        return $delimiter ?: ',';
    }
    
    /**
     * Clean header value.
     */
    private function cleanHeader($header) {
        // Remove BOM
        $header = str_replace("\xEF\xBB\xBF", '', $header);
        
        // Trim whitespace
        $header = trim($header);
        
        // Remove quotes
        $header = trim($header, '"\'');
        
        // Normalize whitespace
        $header = preg_replace('/\s+/', ' ', $header);
        
        return $header;
    }
}