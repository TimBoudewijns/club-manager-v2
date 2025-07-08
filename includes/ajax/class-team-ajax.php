<?php

/**
 * Handle team-related AJAX requests.
 */
class Club_Manager_Team_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_create_team', array($this, 'create_team'));
        add_action('wp_ajax_cm_get_teams', array($this, 'get_teams'));
        add_action('wp_ajax_cm_get_my_teams', array($this, 'get_my_teams'));
        add_action('wp_ajax_cm_get_all_club_teams', array($this, 'get_all_club_teams'));
        add_action('wp_ajax_cm_create_club_team', array($this, 'create_club_team'));
        add_action('wp_ajax_cm_get_team_trainers', array($this, 'get_team_trainers'));
        add_action('wp_ajax_cm_get_available_trainers', array($this, 'get_available_trainers'));
        add_action('wp_ajax_cm_assign_trainer_to_team', array($this, 'assign_trainer_to_team'));
        add_action('wp_ajax_cm_remove_trainer_from_team', array($this, 'remove_trainer_from_team'));
        add_action('wp_ajax_cm_update_team', array($this, 'update_team'));
        add_action('wp_ajax_cm_delete_team', array($this, 'delete_team'));
        add_action('wp_ajax_cm_save_season_preference', array($this, 'save_season_preference'));
    }
    
    /**
     * Create a new team.
     */
    public function create_team() {
        $user_id = $this->verify_request();
        
        // Check if user can create teams
        if (!Club_Manager_User_Permissions_Helper::can_create_teams($user_id)) {
            wp_send_json_error('You do not have permission to create teams');
            return;
        }
        
        $name = $this->get_post_data('name');
        $coach = $this->get_post_data('coach');
        $season = $this->get_post_data('season');
        
        if (empty($name) || empty($coach) || empty($season)) {
            wp_send_json_error('Missing required fields');
            return;
        }
        
        $team_model = new Club_Manager_Team_Model();
        $result = $team_model->create([
            'name' => $name,
            'coach' => $coach,
            'season' => $season,
            'created_by' => $user_id
        ]);
        
        if ($result) {
            wp_send_json_success([
                'id' => $result,
                'message' => 'Team created successfully'
            ]);
        } else {
            wp_send_json_error('Failed to create team');
        }
    }
    
    /**
     * Get teams for current user and season (legacy - kept for compatibility).
     */
    public function get_teams() {
        $user_id = $this->verify_request();
        
        $season = $this->get_post_data('season');
        
        $team_model = new Club_Manager_Team_Model();
        $teams = $team_model->get_user_teams($user_id, $season);
        
        wp_send_json_success($teams);
    }
    
    /**
     * Get teams for "My Teams" tab based on user role.
     */
    public function get_my_teams() {
        $user_id = $this->verify_request();
        
        $season = $this->get_post_data('season');
        
        $teams = Club_Manager_User_Permissions_Helper::get_my_teams($user_id, $season);
        
        wp_send_json_success($teams);
    }
    
    /**
     * Get all club teams (for owners/managers).
     */
    public function get_all_club_teams() {
        $user_id = $this->verify_request();
        
        // Check if user can manage teams
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $season = $this->get_post_data('season');
        
        // Get all club member IDs
        $club_member_ids = $this->get_club_member_ids($user_id);
        
        if (empty($club_member_ids)) {
            wp_send_json_success([]);
            return;
        }
        
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
        
        // Add trainer count for each team
        foreach ($teams as $team) {
            $trainer_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $trainers_table WHERE team_id = %d AND is_active = 1",
                $team->id
            ));
            $team->trainer_count = $trainer_count;
        }
        
        wp_send_json_success($teams);
    }
    
    /**
     * Get available trainers for a season.
     */
    public function get_available_trainers() {
        $user_id = $this->verify_request();
        
        // Check if user can manage teams
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $season = $this->get_post_data('season');
        
        global $wpdb;
        $available_trainers = array();
        
        // 1. Get active trainers from WC Teams
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        
        if (!empty($managed_teams)) {
            $wc_team_id = $managed_teams[0]['team_id'];
            
            if (function_exists('wc_memberships_for_teams_get_team')) {
                $team = wc_memberships_for_teams_get_team($wc_team_id);
                
                if ($team && is_object($team) && method_exists($team, 'get_members')) {
                    $members = $team->get_members();
                    
                    foreach ($members as $member) {
                        if (method_exists($member, 'get_user_id')) {
                            $member_user_id = $member->get_user_id();
                            $member_user = get_user_by('id', $member_user_id);
                            
                            if ($member_user) {
                                $available_trainers[] = array(
                                    'id' => $member_user->ID,
                                    'display_name' => $member_user->display_name,
                                    'email' => $member_user->user_email,
                                    'first_name' => get_user_meta($member_user->ID, 'first_name', true),
                                    'last_name' => get_user_meta($member_user->ID, 'last_name', true),
                                    'type' => 'active'
                                );
                            }
                        }
                    }
                }
            }
        }
        
        // 2. Get pending invitations
        if (!empty($managed_teams)) {
            $wc_team_ids = array_column($managed_teams, 'team_id');
            $placeholders = implode(',', array_fill(0, count($wc_team_ids), '%d'));
            
            $pending_invitations = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, pm_email.meta_value as email
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id AND pm_email.meta_key = '_email'
                WHERE p.post_type = 'wc_team_invitation'
                AND p.post_status = 'wcmti-pending'
                AND p.post_parent IN ($placeholders)
                ORDER BY p.post_date DESC",
                ...$wc_team_ids
            ));
            
            foreach ($pending_invitations as $invitation) {
                // Check if this is a Club Manager invitation
                $cm_team_ids = get_post_meta($invitation->ID, '_cm_team_ids', true);
                if ($cm_team_ids) {
                    $available_trainers[] = array(
                        'id' => 'pending_' . $invitation->ID,
                        'display_name' => $invitation->email,
                        'email' => $invitation->email,
                        'first_name' => '',
                        'last_name' => '',
                        'type' => 'pending',
                        'invitation_id' => $invitation->ID
                    );
                }
            }
        }
        
        // If still no trainers, get from Club Manager trainers table
        if (empty($available_trainers)) {
            $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
            $teams_table = Club_Manager_Database::get_table_name('teams');
            
            $trainers = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT u.ID, u.display_name, u.user_email as email,
                        um1.meta_value as first_name, um2.meta_value as last_name
                FROM {$wpdb->users} u
                INNER JOIN $trainers_table tt ON u.ID = tt.trainer_id
                INNER JOIN $teams_table t ON tt.team_id = t.id
                LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                WHERE t.created_by = %d AND tt.is_active = 1
                ORDER BY u.display_name",
                $user_id
            ));
            
            foreach ($trainers as $trainer) {
                $available_trainers[] = array(
                    'id' => $trainer->ID,
                    'display_name' => $trainer->display_name,
                    'email' => $trainer->email,
                    'first_name' => $trainer->first_name,
                    'last_name' => $trainer->last_name,
                    'type' => 'active'
                );
            }
        }
        
        wp_send_json_success($available_trainers);
    }
    
    /**
     * Create a club team (for owners/managers).
     */
    public function create_club_team() {
        $user_id = $this->verify_request();
        
        // Check if user can manage teams
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $name = $this->get_post_data('name');
        $coach = $this->get_post_data('coach');
        $season = $this->get_post_data('season');
        $trainers = isset($_POST['trainers']) ? array_map('intval', $_POST['trainers']) : [];
        
        if (empty($name) || empty($coach) || empty($season)) {
            wp_send_json_error('Missing required fields');
            return;
        }
        
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Create team
            $team_model = new Club_Manager_Team_Model();
            $team_id = $team_model->create([
                'name' => $name,
                'coach' => $coach,
                'season' => $season,
                'created_by' => $user_id
            ]);
            
            if (!$team_id) {
                throw new Exception('Failed to create team');
            }
            
            // Assign trainers if any
            if (!empty($trainers)) {
                $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
                
                foreach ($trainers as $trainer_id) {
                    $wpdb->insert(
                        $trainers_table,
                        [
                            'team_id' => $team_id,
                            'trainer_id' => $trainer_id,
                            'role' => 'trainer',
                            'is_active' => 1,
                            'added_by' => $user_id,
                            'added_at' => current_time('mysql')
                        ],
                        ['%d', '%d', '%s', '%d', '%d', '%s']
                    );
                }
            }
            
            $wpdb->query('COMMIT');
            
            wp_send_json_success([
                'id' => $team_id,
                'message' => 'Team created successfully'
            ]);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Get trainers assigned to a team.
     */
    public function get_team_trainers() {
        $user_id = $this->verify_request();
        
        // Check if user can manage teams
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $team_id = $this->get_post_data('team_id', 'int');
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        $trainers = $wpdb->get_results($wpdb->prepare(
            "SELECT tt.*, u.display_name, u.user_email as email,
                    um1.meta_value as first_name, um2.meta_value as last_name
            FROM $trainers_table tt
            INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
            WHERE tt.team_id = %d AND tt.is_active = 1
            ORDER BY u.display_name",
            $team_id
        ));
        
        wp_send_json_success($trainers);
    }
    
    /**
     * Assign trainer to team.
     */
    public function assign_trainer_to_team() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $team_id = $this->get_post_data('team_id', 'int');
        $trainer_id_raw = $this->get_post_data('trainer_id');
        
        if (!$team_id || !$trainer_id_raw) {
            wp_send_json_error('Missing required fields');
            return;
        }
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        // Check if this is a pending invitation
        if (strpos($trainer_id_raw, 'pending:') === 0) {
            // Extract email from pending:email format
            $email = substr($trainer_id_raw, 8);
            
            // Store the pending assignment
            $pending_assignments_table = Club_Manager_Database::get_table_name('pending_trainer_assignments');
            
            // Create table if it doesn't exist
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE IF NOT EXISTS $pending_assignments_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                team_id mediumint(9) NOT NULL,
                trainer_email varchar(255) NOT NULL,
                assigned_by bigint(20) NOT NULL,
                assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY team_email (team_id, trainer_email)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Insert pending assignment
            $result = $wpdb->replace(
                $pending_assignments_table,
                [
                    'team_id' => $team_id,
                    'trainer_email' => $email,
                    'assigned_by' => $user_id,
                    'assigned_at' => current_time('mysql')
                ],
                ['%d', '%s', '%d', '%s']
            );
            
            if ($result) {
                wp_send_json_success(['message' => 'Trainer will be assigned when they accept the invitation']);
            } else {
                wp_send_json_error('Failed to create pending assignment');
            }
            
        } else {
            // Regular trainer assignment
            $trainer_id = intval($trainer_id_raw);
            
            // Check if already assigned
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $trainers_table WHERE team_id = %d AND trainer_id = %d",
                $team_id, $trainer_id
            ));
            
            if ($existing) {
                wp_send_json_error('Trainer already assigned to this team');
                return;
            }
            
            // Assign trainer
            $result = $wpdb->insert(
                $trainers_table,
                [
                    'team_id' => $team_id,
                    'trainer_id' => $trainer_id,
                    'role' => 'trainer',
                    'is_active' => 1,
                    'added_by' => $user_id,
                    'added_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%d', '%d', '%s']
            );
            
            if ($result) {
                wp_send_json_success(['message' => 'Trainer assigned successfully']);
            } else {
                wp_send_json_error('Failed to assign trainer');
            }
        }
    }
    
    /**
     * Remove trainer from team.
     */
    public function remove_trainer_from_team() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $team_id = $this->get_post_data('team_id', 'int');
        $trainer_id = $this->get_post_data('trainer_id', 'int');
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        $result = $wpdb->delete(
            $trainers_table,
            [
                'team_id' => $team_id,
                'trainer_id' => $trainer_id
            ],
            ['%d', '%d']
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Trainer removed successfully']);
        } else {
            wp_send_json_error('Failed to remove trainer');
        }
    }
    
    /**
     * Update a team.
     */
    public function update_team() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $team_id = $this->get_post_data('team_id', 'int');
        $name = $this->get_post_data('name');
        $coach = $this->get_post_data('coach');
        
        if (empty($name) || empty($coach)) {
            wp_send_json_error('Missing required fields');
            return;
        }
        
        // Verify team belongs to club
        if (!$this->is_club_team($team_id, $user_id)) {
            wp_send_json_error('Unauthorized access to team');
            return;
        }
        
        $team_model = new Club_Manager_Team_Model();
        $result = $team_model->update($team_id, [
            'name' => $name,
            'coach' => $coach
        ]);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Team updated successfully']);
        } else {
            wp_send_json_error('Failed to update team');
        }
    }
    
    /**
     * Delete a team.
     */
    public function delete_team() {
        $user_id = $this->verify_request();
        
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $team_id = $this->get_post_data('team_id', 'int');
        
        // Verify team belongs to club
        if (!$this->is_club_team($team_id, $user_id)) {
            wp_send_json_error('Unauthorized access to team');
            return;
        }
        
        global $wpdb;
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Remove team trainers
            $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
            $wpdb->delete($trainers_table, ['team_id' => $team_id], ['%d']);
            
            // Remove team players
            $team_players_table = Club_Manager_Database::get_table_name('team_players');
            $wpdb->delete($team_players_table, ['team_id' => $team_id], ['%d']);
            
            // Delete team
            $team_model = new Club_Manager_Team_Model();
            $result = $team_model->delete($team_id);
            
            if (!$result) {
                throw new Exception('Failed to delete team');
            }
            
            $wpdb->query('COMMIT');
            
            wp_send_json_success(['message' => 'Team deleted successfully']);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Save season preference.
     */
    public function save_season_preference() {
        $user_id = $this->verify_request();
        
        $season = $this->get_post_data('season');
        update_user_meta($user_id, 'cm_preferred_season', $season);
        
        wp_send_json_success();
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
                        if (method_exists($member, 'get_user_id')) {
                            $member_ids[] = $member->get_user_id();
                        }
                    }
                }
            }
        }
        
        // Remove duplicates
        $member_ids = array_unique($member_ids);
        
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