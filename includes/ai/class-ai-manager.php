<?php

/**
 * AI Manager for generating player advice.
 */
class Club_Manager_AI_Manager {
    
    private $openai_client;
    
    public function __construct() {
        $this->openai_client = new Club_Manager_OpenAI_Client();
    }
    
    /**
     * Initialize AI manager.
     */
    public function init() {
        // Hook for scheduled advice generation
        add_action('cm_generate_player_advice', array($this, 'generate_advice'), 10, 3);
    }
    
    /**
     * Generate advice for a player.
     */
    public function generate_advice($player_id, $team_id, $season) {
        // Check if OpenAI API key is defined
        if (!defined('OPENAI_API_KEY')) {
            error_log('Club Manager: OpenAI API key not defined');
            return;
        }
        
        // Get player and evaluation data
        $player_data = $this->get_player_data($player_id, $team_id, $season);
        if (!$player_data) {
            error_log('Club Manager: Player data not found for advice generation');
            return;
        }
        
        // Get evaluations
        $evaluations = $this->get_evaluation_summary($player_id, $team_id, $season);
        if (empty($evaluations)) {
            error_log('Club Manager: No evaluations found for player');
            return;
        }
        
        // Generate prompt
        $prompt = $this->create_advice_prompt($player_data, $evaluations);
        
        // Call OpenAI API
        $advice = $this->openai_client->generate_completion($prompt);
        
        if ($advice) {
            $this->save_advice($player_id, $team_id, $season, $advice);
        }
    }
    
    /**
     * Get player data.
     */
    private function get_player_data($player_id, $team_id, $season) {
        global $wpdb;
        
        $players_table = Club_Manager_Database::get_table_name('players');
        $team_players_table = Club_Manager_Database::get_table_name('team_players');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, tp.position 
            FROM $players_table p
            JOIN $team_players_table tp ON p.id = tp.player_id
            WHERE p.id = %d AND tp.team_id = %d AND tp.season = %s",
            $player_id, $team_id, $season
        ));
    }
    
    /**
     * Get evaluation summary.
     */
    private function get_evaluation_summary($player_id, $team_id, $season) {
        $evaluation_model = new Club_Manager_Evaluation_Model();
        $evaluations = $evaluation_model->get_latest_by_category($player_id, $team_id, $season);
        
        $summary = [];
        foreach ($evaluations as $eval) {
            $category = str_replace('_', ' ', $eval->category);
            $category = ucwords($category);
            
            if (!isset($summary[$category])) {
                $summary[$category] = [];
            }
            
            if ($eval->subcategory) {
                $subcategory = str_replace('_', ' ', $eval->subcategory);
                $subcategory = ucwords($subcategory);
                $summary[$category][$subcategory] = round($eval->score, 1);
            } else {
                $summary[$category]['Overall'] = round($eval->score, 1);
            }
        }
        
        return $summary;
    }
    
    /**
     * Create advice prompt.
     */
    private function create_advice_prompt($player_data, $evaluations) {
        $position = $player_data->position ?: 'Unknown';
        $age = $this->calculate_age($player_data->birth_date);
        
        $evaluation_text = $this->format_evaluations($evaluations);
        
        return "You are an expert hockey coach analyzing a player's performance evaluation. Generate personalized training advice for the coach to help improve this player.

Player Information:
- Name: {$player_data->first_name} {$player_data->last_name}
- Position: {$position}
- Age: {$age} years old

Performance Evaluation Scores (out of 10):
{$evaluation_text}

Instructions:
1. Start with 1-2 sentences explaining how the evaluation scores relate to what's important for a {$position} position.
2. Then provide 4-5 specific, actionable training tips based on the lowest scores and position requirements.
3. Write in English language.
4. Maximum 1000 characters total.
5. Be concise, practical, and focused on immediate improvements.";
    }
    
    /**
     * Format evaluations for prompt.
     */
    private function format_evaluations($evaluations) {
        $text = "";
        foreach ($evaluations as $category => $subcategories) {
            $text .= "\n$category:\n";
            foreach ($subcategories as $sub => $score) {
                if ($sub !== 'Overall') {
                    $text .= "  - $sub: $score/10\n";
                }
            }
            if (isset($subcategories['Overall'])) {
                $text .= "  Overall: {$subcategories['Overall']}/10\n";
            }
        }
        return $text;
    }
    
    /**
     * Calculate age from birth date.
     */
    private function calculate_age($birth_date) {
        $birth = new DateTime($birth_date);
        $today = new DateTime();
        $age = $today->diff($birth);
        return $age->y;
    }
    
    /**
     * Save generated advice.
     */
    private function save_advice($player_id, $team_id, $season, $advice) {
        global $wpdb;
        
        $advice_table = Club_Manager_Database::get_table_name('player_advice');
        
        // Mark existing advice as old
        $wpdb->update(
            $advice_table,
            ['status' => 'old'],
            [
                'player_id' => $player_id,
                'team_id' => $team_id,
                'season' => $season
            ],
            ['%s'],
            ['%d', '%d', '%s']
        );
        
        // Insert new advice
        $wpdb->insert(
            $advice_table,
            [
                'player_id' => $player_id,
                'team_id' => $team_id,
                'season' => $season,
                'advice' => $advice,
                'status' => 'current',
                'generated_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );
    }
} 
