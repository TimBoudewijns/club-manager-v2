<?php

/**
 * OpenAI API client.
 */
class Club_Manager_OpenAI_Client {
    
    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-4o';
    
    public function __construct() {
        $this->api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
    }
    
    /**
     * Generate completion from OpenAI.
     */
    public function generate_completion($prompt, $max_tokens = 300) {
        if (empty($this->api_key)) {
            error_log('Club Manager: OpenAI API key not configured');
            return false;
        }
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert hockey coach providing training advice based on player evaluations.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => $max_tokens
        ];
        
        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->api_key
                ],
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($options);
        $result = @file_get_contents($this->api_url, false, $context);
        
        if ($result === false) {
            error_log('Club Manager: Failed to call OpenAI API');
            return false;
        }
        
        $response = json_decode($result, true);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        } else {
            error_log('Club Manager: Invalid response from OpenAI API');
            return false;
        }
    }
    
    /**
     * Set custom model.
     */
    public function set_model($model) {
        $this->model = $model;
    }
    
    /**
     * Check if API key is configured.
     */
    public function is_configured() {
        return !empty($this->api_key);
    }
} 
