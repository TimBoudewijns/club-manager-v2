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
        
        // Add trainer info for each team
        if (!empty($teams)) {
            global $wpdb;
            $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
            
            $pending_assignments_table = Club_Manager_Database::get_table_name('pending_trainer_assignments');
            
            foreach ($teams as $team) {
                $trainer_info = array();
                
                // 1. Get active trainers
                $trainers = $wpdb->get_results($wpdb->prepare(
                    "SELECT u.ID, u.display_name, u.user_email,
                            um1.meta_value as first_name,
                            um2.meta_value as last_name
                    FROM $trainers_table tt
                    INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
                    LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                    LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                    WHERE tt.team_id = %d AND tt.is_active = 1
                    ORDER BY u.display_name",
                    $team->id
                ));
                
                // Add active trainers to the list
                foreach ($trainers as $trainer) {
                    if (!empty($trainer->first_name) || !empty($trainer->last_name)) {
                        $name = trim($trainer->first_name . ' ' . $trainer->last_name);
                    } else {
                        $name = $trainer->display_name;
                    }
                    $trainer_info[] = array(
                        'name' => $name,
                        'email' => $trainer->user_email,
                        'type' => 'active'
                    );
                }
                
                // 2. Check for pending assignments (trainers who were invited but haven't accepted yet)
                $pending_emails = array(); // Track which emails have pending assignments
                
                if ($wpdb->get_var("SHOW TABLES LIKE '$pending_assignments_table'") === $pending_assignments_table) {
                    $pending_assignments = $wpdb->get_results($wpdb->prepare(
                        "SELECT trainer_email FROM $pending_assignments_table WHERE team_id = %d",
                        $team->id
                    ));
                    
                    foreach ($pending_assignments as $pending) {
                        // Check if this trainer is not already active
                        $is_active = false;
                        foreach ($trainer_info as $info) {
                            if ($info['email'] === $pending->trainer_email && $info['type'] === 'active') {
                                $is_active = true;
                                break;
                            }
                        }
                        
                        if (!$is_active) {
                            $pending_emails[] = strtolower($pending->trainer_email);
                            $trainer_info[] = array(
                                'name' => $pending->trainer_email . ' (Invitation Pending)',
                                'email' => $pending->trainer_email,
                                'type' => 'pending'
                            );
                        }
                    }
                }
                
                // 3. Also check WC Teams invitations with our custom meta
                if (function_exists('wc_memberships_for_teams')) {
                    // Get pending invitations that have this team in their meta
                    $invitations = $wpdb->get_results($wpdb->prepare(
                        "SELECT p.ID, p.post_title, pm_email.meta_value as email
                        FROM {$wpdb->posts} p
                        LEFT JOIN {$wpdb->postmeta} pm_teams ON p.ID = pm_teams.post_id AND pm_teams.meta_key = '_cm_team_ids'
                        LEFT JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id AND pm_email.meta_key = '_email'
                        WHERE p.post_type = 'wc_team_invitation'
                        AND p.post_status = 'wcmti-pending'
                        AND pm_teams.meta_value LIKE %s",
                        '%"' . $team->id . '"%'
                    ));
                    
                    foreach ($invitations as $invitation) {
                        $email = $invitation->email ?: $invitation->post_title;
                        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            // Check if not already in list (as active or pending from pending_assignments)
                            $already_exists = false;
                            $email_lower = strtolower($email);
                            
                            // Check if already in trainer_info
                            foreach ($trainer_info as $info) {
                                if (strtolower($info['email']) === $email_lower) {
                                    $already_exists = true;
                                    break;
                                }
                            }
                            
                            // Also check if already in pending_emails from pending_assignments
                            if (!$already_exists && in_array($email_lower, $pending_emails)) {
                                $already_exists = true;
                            }
                            
                            if (!$already_exists) {
                                $trainer_info[] = array(
                                    'name' => $email . ' (Invitation Pending)',
                                    'email' => $email,
                                    'type' => 'pending'
                                );
                            }
                        }
                    }
                }
                
                // Set counts and names
                $team->trainer_count = count($trainer_info);
                $team->active_trainer_count = count(array_filter($trainer_info, function($t) { return $t['type'] === 'active'; }));
                $team->pending_trainer_count = count(array_filter($trainer_info, function($t) { return $t['type'] === 'pending'; }));
                
                // Create trainer_names string
                if (!empty($trainer_info)) {
                    $trainer_names = array_map(function($t) { return $t['name']; }, $trainer_info);
                    $team->trainer_names = implode(', ', $trainer_names);
                    error_log("Team {$team->name} has trainers: " . $team->trainer_names);
                } else {
                    $team->trainer_names = '';  // Use empty string instead of null
                    error_log("Team {$team->name} has no trainers");
                }
            }
        }
        
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
        
        // Add trainer info for each team
        foreach ($teams as $team) {
            $trainer_info = array();
            
            // 1. Get active trainers
            $active_trainers = $wpdb->get_results($wpdb->prepare(
                "SELECT u.display_name, u.user_email, 
                        um1.meta_value as first_name, um2.meta_value as last_name
                FROM $trainers_table tt
                INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
                LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                WHERE tt.team_id = %d AND tt.is_active = 1
                ORDER BY u.display_name",
                $team->id
            ));
            
            foreach ($active_trainers as $trainer) {
                if (!empty($trainer->first_name) || !empty($trainer->last_name)) {
                    $name = trim($trainer->first_name . ' ' . $trainer->last_name);
                } else {
                    $name = $trainer->display_name;
                }
                $trainer_info[] = array(
                    'name' => $name,
                    'email' => $trainer->user_email,
                    'type' => 'active'
                );
            }
            
            // 2. Check for pending assignments (trainers who were invited but haven't accepted yet)
            $pending_assignments_table = Club_Manager_Database::get_table_name('pending_trainer_assignments');
            $pending_emails = array(); // Track which emails have pending assignments
            
            // Check if the table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$pending_assignments_table'") === $pending_assignments_table) {
                $pending_assignments = $wpdb->get_results($wpdb->prepare(
                    "SELECT trainer_email FROM $pending_assignments_table WHERE team_id = %d",
                    $team->id
                ));
                
                foreach ($pending_assignments as $pending) {
                    // Only add if this trainer is not already active
                    $is_active = false;
                    foreach ($trainer_info as $info) {
                        if ($info['email'] === $pending->trainer_email && $info['type'] === 'active') {
                            $is_active = true;
                            break;
                        }
                    }
                    
                    if (!$is_active) {
                        $pending_emails[] = strtolower($pending->trainer_email);
                        $trainer_info[] = array(
                            'name' => $pending->trainer_email . ' (Invitation Pending)',
                            'email' => $pending->trainer_email,
                            'type' => 'pending'
                        );
                    }
                }
            }
            
            // 3. Also check WC Teams invitations with our custom meta
            if (function_exists('wc_memberships_for_teams')) {
                // Get pending invitations that have this team in their meta
                $invitations = $wpdb->get_results($wpdb->prepare(
                    "SELECT p.ID, p.post_title, pm_email.meta_value as email
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm_teams ON p.ID = pm_teams.post_id AND pm_teams.meta_key = '_cm_team_ids'
                    LEFT JOIN {$wpdb->postmeta} pm_email ON p.ID = pm_email.post_id AND pm_email.meta_key = '_email'
                    WHERE p.post_type = 'wc_team_invitation'
                    AND p.post_status = 'wcmti-pending'
                    AND pm_teams.meta_value LIKE %s",
                    '%"' . $team->id . '"%'
                ));
                
                foreach ($invitations as $invitation) {
                    $email = $invitation->email ?: $invitation->post_title;
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        // Check if not already in list (as active or pending from pending_assignments)
                        $already_exists = false;
                        $email_lower = strtolower($email);
                        
                        // Check if already in trainer_info
                        foreach ($trainer_info as $info) {
                            if (strtolower($info['email']) === $email_lower) {
                                $already_exists = true;
                                break;
                            }
                        }
                        
                        // Also check if already in pending_emails from pending_assignments
                        if (!$already_exists && in_array($email_lower, $pending_emails)) {
                            $already_exists = true;
                        }
                        
                        if (!$already_exists) {
                            $trainer_info[] = array(
                                'name' => $email . ' (Invitation Pending)',
                                'email' => $email,
                                'type' => 'pending'
                            );
                        }
                    }
                }
            }
            
            // Set counts and names
            $team->trainer_count = count($trainer_info);
            $team->active_trainer_count = count(array_filter($trainer_info, function($t) { return $t['type'] === 'active'; }));
            $team->pending_trainer_count = count(array_filter($trainer_info, function($t) { return $t['type'] === 'pending'; }));
            
            if (!empty($trainer_info)) {
                $trainer_names = array_map(function($t) { return $t['name']; }, $trainer_info);
                $team->trainer_names = implode(', ', $trainer_names);
            } else {
                $team->trainer_names = '';  // Use empty string instead of null for consistency
            }
        }
        
        wp_send_json_success($teams);
    }
    
    /**
     * Get available trainers for a season - includes both active trainers and pending invitations.
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
        $processed_users = array();
        $processed_emails = array();
        
        // Get club member IDs first
        $club_member_ids = $this->get_club_member_ids($user_id);
        
        // 1. FIRST: Get ALL trainers from Club Manager system (dit was het probleem!)
        if (!empty($club_member_ids)) {
            $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
            $teams_table = Club_Manager_Database::get_table_name('teams');
            
            $placeholders = implode(',', array_fill(0, count($club_member_ids), '%d'));
            
            // Get ALL UNIQUE trainers who have EVER been assigned to ANY team in the club
            $existing_trainers = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT u.ID, u.display_name, u.user_email as email,
                        um1.meta_value as first_name, um2.meta_value as last_name
                FROM {$wpdb->users} u
                INNER JOIN $trainers_table tt ON u.ID = tt.trainer_id
                INNER JOIN $teams_table t ON tt.team_id = t.id
                LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                WHERE t.created_by IN ($placeholders)
                ORDER BY u.display_name",
                ...$club_member_ids
            ));
            
            foreach ($existing_trainers as $trainer) {
                $processed_users[] = $trainer->ID;
                $processed_emails[] = strtolower($trainer->email);
                
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
        
        // 2. Then get WC Teams members (but skip if already processed)
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        
        if (!empty($managed_teams)) {
            $wc_team_ids = array_column($managed_teams, 'team_id');
            
            // Get active trainers from WC Teams
            foreach ($managed_teams as $team_info) {
                $wc_team_id = $team_info['team_id'];
                
                if (function_exists('wc_memberships_for_teams_get_team')) {
                    $team = wc_memberships_for_teams_get_team($wc_team_id);
                    
                    if ($team && is_object($team) && method_exists($team, 'get_members')) {
                        $members = $team->get_members();
                        
                        foreach ($members as $member) {
                            if (method_exists($member, 'get_user_id')) {
                                $member_user_id = $member->get_user_id();
                                
                                // Skip if already processed
                                if (!in_array($member_user_id, $processed_users)) {
                                    $member_user = get_user_by('id', $member_user_id);
                                    
                                    if ($member_user) {
                                        $processed_users[] = $member_user_id;
                                        $processed_emails[] = strtolower($member_user->user_email);
                                        
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
            }
            
            // 3. Get pending invitations
            if (!empty($wc_team_ids)) {
                $placeholders = implode(',', array_fill(0, count($wc_team_ids), '%d'));
                
                $invitation_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT p.ID
                    FROM {$wpdb->posts} p
                    WHERE p.post_type = 'wc_team_invitation'
                    AND p.post_status = 'wcmti-pending'
                    AND p.post_parent IN ($placeholders)
                    ORDER BY p.post_date DESC",
                    ...$wc_team_ids
                ));
                
                foreach ($invitation_ids as $invitation_id) {
                    $meta_data = get_post_meta($invitation_id);
                    
                    $email = null;
                    if (isset($meta_data['_email'][0]) && !empty($meta_data['_email'][0])) {
                        $email = $meta_data['_email'][0];
                    } elseif (isset($meta_data['_recipient_email'][0]) && !empty($meta_data['_recipient_email'][0])) {
                        $email = $meta_data['_recipient_email'][0];
                    } else {
                        $invitation_post = get_post($invitation_id);
                        if ($invitation_post && filter_var($invitation_post->post_title, FILTER_VALIDATE_EMAIL)) {
                            $email = $invitation_post->post_title;
                        }
                    }
                    
                    if (empty($email) || in_array(strtolower($email), $processed_emails)) {
                        continue;
                    }
                    
                    $processed_emails[] = strtolower($email);
                    
                    $available_trainers[] = array(
                        'id' => 'pending_' . $invitation_id,
                        'display_name' => $email,
                        'email' => $email,
                        'first_name' => '',
                        'last_name' => '',
                        'type' => 'pending',
                        'invitation_id' => $invitation_id
                    );
                }
            }
        }
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Club Manager - Available trainers: ' . json_encode(array(
                'total' => count($available_trainers),
                'active' => count(array_filter($available_trainers, function($t) { return $t['type'] === 'active'; })),
                'pending' => count(array_filter($available_trainers, function($t) { return $t['type'] === 'pending'; }))
            )));
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
            
            // Check if assignment already exists to prevent duplicates
            $existing_assignment = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $pending_assignments_table 
                WHERE team_id = %d AND trainer_email = %s",
                $team_id, $email
            ));
            
            if ($existing_assignment) {
                wp_send_json_error('Trainer is already assigned to this team (pending acceptance)');
                return;
            }
            
            // Insert pending assignment
            $result = $wpdb->insert(
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
                wp_send_json_error('Trainer is already assigned to this team');
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
        
        // First get the trainer's email before removing
        $trainer_email = null;
        $trainer = get_user_by('id', $trainer_id);
        if ($trainer) {
            $trainer_email = $trainer->user_email;
        }
        
        $result = $wpdb->delete(
            $trainers_table,
            [
                'team_id' => $team_id,
                'trainer_id' => $trainer_id
            ],
            ['%d', '%d']
        );
        
        // Also clean up any pending assignments for this trainer on this team
        if ($trainer_email) {
            $pending_assignments_table = Club_Manager_Database::get_table_name('pending_trainer_assignments');
            
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$pending_assignments_table'") === $pending_assignments_table) {
                $wpdb->delete(
                    $pending_assignments_table,
                    [
                        'team_id' => $team_id,
                        'trainer_email' => $trainer_email
                    ],
                    ['%d', '%s']
                );
            }
        }
        
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