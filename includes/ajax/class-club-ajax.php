<?php

/**
 * Handle club-related AJAX requests.
 */
class Club_Manager_Club_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_get_club_teams', array($this, 'get_club_teams'));
        add_action('wp_ajax_cm_get_club_team_players', array($this, 'get_club_team_players'));
        add_action('wp_ajax_cm_get_club_player_evaluations', array($this, 'get_club_player_evaluations'));
        add_action('wp_ajax_cm_get_club_player_advice', array($this, 'get_club_player_advice'));
    }
    
    /**
     * Get all teams in the club.
     */
    public function get_club_teams() {
        $user_id = $this->verify_request();
        
        // Verify user can view club teams
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access to club teams');
            return;
        }
        
        $season = $this->get_post_data('season');
        
        // Get all club member IDs
        $club_member_ids = $this->get_club_member_ids($user_id);
        
        if (empty($club_member_ids)) {
            wp_send_json_success([]);
            return;
        }
        
        // Get teams for all club members
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        $placeholders = implode(',', array_fill(0, count($club_member_ids), '%d'));
        $query_args = array_merge($club_member_ids, [$season]);
        
        $teams = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.display_name as owner_name 
            FROM $teams_table t
            LEFT JOIN {$wpdb->users} u ON t.created_by = u.ID
            WHERE t.created_by IN ($placeholders) AND t.season = %s
            ORDER BY t.name",
            ...$query_args
        ));
        
        // For each team, get the trainers
        foreach ($teams as $team) {
            $trainers = $wpdb->get_results($wpdb->prepare(
                "SELECT u.display_name, u.first_name, u.last_name, tt.role 
                FROM $trainers_table tt
                LEFT JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
                LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                WHERE tt.team_id = %d AND tt.is_active = 1
                ORDER BY u.display_name",
                $team->id
            ));
            
            if (!empty($trainers)) {
                $trainer_names = array();
                foreach ($trainers as $trainer) {
                    // Use first/last name if available, otherwise display_name
                    if (!empty($trainer->first_name) || !empty($trainer->last_name)) {
                        $name = trim($trainer->first_name . ' ' . $trainer->last_name);
                    } else {
                        $name = $trainer->display_name;
                    }
                    $trainer_names[] = $name;
                }
                $team->trainer_names = implode(', ', $trainer_names);
            } else {
                $team->trainer_names = null;
            }
        }
        
        wp_send_json_success($teams);
    }
    
    /**
     * Get players for a club team.
     */
    public function get_club_team_players() {
        $user_id = $this->verify_request();
        
        // Verify user can view club teams
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access to club teams');
            return;
        }
        
        $team_id = $this->get_post_data('team_id', 'int');
        $season = $this->get_post_data('season');
        
        // Verify team belongs to club
        if (!$this->is_club_team($team_id, $user_id)) {
            wp_send_json_error('Team not found in club');
            return;
        }
        
        $player_model = new Club_Manager_Player_Model();
        $players = $player_model->get_team_players($team_id, $season);
        
        wp_send_json_success($players);
    }
    
    /**
     * Get evaluations for a club player.
     */
    public function get_club_player_evaluations() {
        $user_id = $this->verify_request();
        
        // Verify user can view club teams
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access to club teams');
            return;
        }
        
        $team_id = $this->get_post_data('team_id', 'int');
        $player_id = $this->get_post_data('player_id', 'int');
        $season = $this->get_post_data('season');
        
        // Verify team belongs to club
        if (!$this->is_club_team($team_id, $user_id)) {
            wp_send_json_error('Team not found in club');
            return;
        }
        
        $evaluation_model = new Club_Manager_Evaluation_Model();
        $evaluations = $evaluation_model->get_player_evaluations($player_id, $team_id, $season);
        $averages = $evaluation_model->get_category_averages($player_id, $team_id, $season);
        
        wp_send_json_success([
            'evaluations' => $evaluations,
            'averages' => $averages
        ]);
    }
    
    /**
     * Get AI advice for a club player.
     */
    public function get_club_player_advice() {
        $user_id = $this->verify_request();
        
        // Verify user can view club teams
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access to club teams');
            return;
        }
        
        $team_id = $this->get_post_data('team_id', 'int');
        $player_id = $this->get_post_data('player_id', 'int');
        $season = $this->get_post_data('season');
        
        // Verify team belongs to club
        if (!$this->is_club_team($team_id, $user_id)) {
            wp_send_json_error('Team not found in club');
            return;
        }
        
        global $wpdb;
        $advice_table = Club_Manager_Database::get_table_name('player_advice');
        
        $advice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $advice_table 
            WHERE player_id = %d AND team_id = %d AND season = %s
            ORDER BY generated_at DESC
            LIMIT 1",
            $player_id, $team_id, $season
        ));
        
        if ($advice) {
            wp_send_json_success([
                'advice' => $advice->advice,
                'generated_at' => $advice->generated_at,
                'status' => $advice->status
            ]);
        } else {
            // Check if player has evaluations
            $evaluation_model = new Club_Manager_Evaluation_Model();
            $evaluations = $evaluation_model->get_player_evaluations($player_id, $team_id, $season);
            
            $status = empty($evaluations) ? 'no_evaluations' : 'no_advice_yet';
            
            wp_send_json_success([
                'advice' => null,
                'status' => $status
            ]);
        }
    }
    
    /**
     * Get all member IDs in the user's club.
     */
    private function get_club_member_ids($user_id) {
        if (!class_exists('Club_Manager_Teams_Helper')) {
            return [];
        }
        
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        
        if (empty($managed_teams)) {
            return [];
        }
        
        $member_ids = [];
        
        // Get all members from managed teams
        foreach ($managed_teams as $team_info) {
            $team_id = $team_info['team_id'];
            
            // Try to get team members
            if (function_exists('wc_memberships_for_teams_get_team')) {
                $team = wc_memberships_for_teams_get_team($team_id);
                
                if ($team && is_object($team) && method_exists($team, 'get_members')) {
                    $members = $team->get_members();
                    
                    foreach ($members as $member) {
                        if (method_exists($member, 'get_user_id') && method_exists($member, 'get_role')) {
                            $member_role = $member->get_role();
                            // Only include owners and managers
                            if (in_array($member_role, ['owner', 'manager'])) {
                                $member_ids[] = $member->get_user_id();
                            }
                        }
                    }
                }
            }
        }
        
        // Remove duplicates
        $member_ids = array_unique($member_ids);
        
        // If no members found through API, get from database
        if (empty($member_ids)) {
            global $wpdb;
            
            // Get team IDs
            $team_ids = array_map(function($team) {
                return $team['team_id'];
            }, $managed_teams);
            
            if (!empty($team_ids)) {
                $placeholders = implode(',', array_fill(0, count($team_ids), '%d'));
                
                // Get members with owner or manager role only
                $results = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT pm1.meta_value 
                    FROM {$wpdb->postmeta} pm1
                    INNER JOIN {$wpdb->postmeta} pm2 
                        ON pm1.post_id = pm2.post_id 
                        AND pm2.meta_key = '_role' 
                        AND pm2.meta_value IN ('owner', 'manager')
                    WHERE pm1.post_id IN ($placeholders) 
                    AND pm1.meta_key = '_member_id'",
                    ...$team_ids
                ));
                
                $member_ids = array_map('intval', $results);
            }
        }
        
        // Always include the current user
        if (!in_array($user_id, $member_ids)) {
            $member_ids[] = $user_id;
        }
        
        return $member_ids;
    }
    
    /**
     * Check if a team belongs to the user's club.
     */
    private function is_club_team($team_id, $user_id) {
        global $wpdb;
        
        $teams_table = Club_Manager_Database::get_table_name('teams');
        $team_owner = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $team_id
        ));
        
        if (!$team_owner) {
            return false;
        }
        
        $club_member_ids = $this->get_club_member_ids($user_id);
        
        return in_array($team_owner, $club_member_ids);
    }
}