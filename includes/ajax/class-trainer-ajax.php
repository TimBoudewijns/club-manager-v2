<?php

/**
 * Handle trainer-related AJAX requests using official WC Teams invitations.
 */
class Club_Manager_Trainer_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions.
     */
    public function init() {
        add_action('wp_ajax_cm_get_managed_teams', array($this, 'get_managed_teams'));
        add_action('wp_ajax_cm_get_pending_invitations', array($this, 'get_pending_invitations'));
        add_action('wp_ajax_cm_get_active_trainers', array($this, 'get_active_trainers'));
        add_action('wp_ajax_cm_get_available_trainers', array($this, 'get_available_trainers'));
        add_action('wp_ajax_cm_invite_trainer', array($this, 'invite_trainer'));
        add_action('wp_ajax_cm_cancel_invitation', array($this, 'cancel_invitation'));
        add_action('wp_ajax_cm_update_trainer', array($this, 'update_trainer'));
        add_action('wp_ajax_cm_remove_trainer', array($this, 'remove_trainer'));
    }
    
    /**
     * Get teams managed by the current user.
     */
    public function get_managed_teams() {
        $user_id = $this->verify_request();
        
        // Verify user can manage trainers (must be owner or manager)
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $season = $this->get_post_data('season');
        
        // Get teams where user is owner or manager
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $teams = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, season FROM $teams_table 
            WHERE created_by = %d AND season = %s
            ORDER BY name",
            $user_id, $season
        ));
        
        wp_send_json_success($teams);
    }

    /**
     * Get available trainers for a season - includes both active trainers and pending invitations.
     * This version properly handles WC Teams invitations
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
        
        // Get user's WC Teams
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        
        if (!empty($managed_teams)) {
            $wc_team_ids = array_column($managed_teams, 'team_id');
            
            // 1. Get active trainers from WC Teams
            foreach ($managed_teams as $team_info) {
                $wc_team_id = $team_info['team_id'];
                
                if (function_exists('wc_memberships_for_teams_get_team')) {
                    $team = wc_memberships_for_teams_get_team($wc_team_id);
                    
                    if ($team && is_object($team) && method_exists($team, 'get_members')) {
                        $members = $team->get_members();
                        
                        foreach ($members as $member) {
                            if (method_exists($member, 'get_user_id')) {
                                $member_user_id = $member->get_user_id();
                                
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
            
            // 2. Get pending invitations - Fixed query
            if (!empty($wc_team_ids)) {
                $placeholders = implode(',', array_fill(0, count($wc_team_ids), '%d'));
                
                // Get invitations using a proper meta query
                $invitation_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT p.ID
                    FROM {$wpdb->posts} p
                    WHERE p.post_type = 'wc_team_invitation'
                    AND p.post_status = 'wcmti-pending'
                    AND p.post_parent IN ($placeholders)
                    ORDER BY p.post_date DESC",
                    ...$wc_team_ids
                ));
                
                // Now get the details for each invitation
                foreach ($invitation_ids as $invitation_id) {
                    // Get all meta data at once
                    $meta_data = get_post_meta($invitation_id);
                    
                    // Extract email - try multiple possible keys
                    $email = null;
                    if (isset($meta_data['_email'][0])) {
                        $email = $meta_data['_email'][0];
                    } elseif (isset($meta_data['_recipient_email'][0])) {
                        $email = $meta_data['_recipient_email'][0];
                    } else {
                        // Sometimes the email might be in the post data itself
                        $invitation_post = get_post($invitation_id);
                        if ($invitation_post && filter_var($invitation_post->post_title, FILTER_VALIDATE_EMAIL)) {
                            $email = $invitation_post->post_title;
                        }
                    }
                    
                    // Skip if no email or already processed
                    if (empty($email) || in_array(strtolower($email), $processed_emails)) {
                        continue;
                    }
                    
                    $processed_emails[] = strtolower($email);
                    
                    // Add to available trainers
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
        
        // 3. Also check for any trainers already in the Club Manager system
        // This is useful for clubs that might have trainers assigned before WC Teams integration
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        $club_member_ids = $this->get_club_member_ids($user_id);
        
        if (!empty($club_member_ids)) {
            $placeholders = implode(',', array_fill(0, count($club_member_ids), '%d'));
            
            $existing_trainers = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT u.ID, u.display_name, u.user_email as email,
                        um1.meta_value as first_name, um2.meta_value as last_name
                FROM {$wpdb->users} u
                INNER JOIN $trainers_table tt ON u.ID = tt.trainer_id
                INNER JOIN $teams_table t ON tt.team_id = t.id
                LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
                WHERE t.created_by IN ($placeholders) AND tt.is_active = 1
                ORDER BY u.display_name",
                ...$club_member_ids
            ));
            
            foreach ($existing_trainers as $trainer) {
                // Only add if not already in the list
                if (!in_array($trainer->ID, $processed_users) && !in_array(strtolower($trainer->email), $processed_emails)) {
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
        }
        
        wp_send_json_success($available_trainers);
    }
    
    /**
     * Get pending trainer invitations from WC Teams.
     */
    public function get_pending_invitations() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        if (!function_exists('wc_memberships_for_teams')) {
            wp_send_json_success([]);
            return;
        }
        
        $invitations = [];
        
        // Get all WC Teams where user is owner/manager
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        $wc_team_ids = [];
        
        foreach ($managed_teams as $team_data) {
            $wc_team_ids[] = $team_data['team_id'];
        }
        
        if (!empty($wc_team_ids)) {
            global $wpdb;
            
            // Get pending invitations for these teams
            $placeholders = implode(',', array_fill(0, count($wc_team_ids), '%d'));
            $query_args = array_merge($wc_team_ids);
            
            $all_invitations = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, p.post_parent, p.post_status, p.post_title, p.post_name, p.post_date
                FROM {$wpdb->posts} p
                WHERE p.post_type = 'wc_team_invitation'
                AND p.post_status = 'wcmti-pending'
                AND p.post_parent IN ($placeholders)
                ORDER BY p.post_date DESC",
                ...$query_args
            ));
            
            $teams_table = Club_Manager_Database::get_table_name('teams');
            
            foreach ($all_invitations as $inv_post) {
                // Get ALL meta data for this invitation
                $all_meta = get_post_meta($inv_post->ID);
                
                // Try to get email from various sources
                $email = null;
                if (!empty($all_meta['_email'])) {
                    $email = $all_meta['_email'][0];
                } elseif (!empty($all_meta['_recipient_email'])) {
                    $email = $all_meta['_recipient_email'][0];
                } elseif (filter_var($inv_post->post_title, FILTER_VALIDATE_EMAIL)) {
                    $email = $inv_post->post_title;
                }
                
                if ($email) {
                    // Get team names from the invitation meta
                    $team_names = [];
                    $cm_team_ids = isset($all_meta['_cm_team_ids']) ? maybe_unserialize($all_meta['_cm_team_ids'][0]) : [];
                    
                    if ($cm_team_ids && is_array($cm_team_ids)) {
                        foreach ($cm_team_ids as $team_id) {
                            $team_name = $wpdb->get_var($wpdb->prepare(
                                "SELECT name FROM $teams_table WHERE id = %d",
                                $team_id
                            ));
                            if ($team_name) {
                                $team_names[] = $team_name;
                            }
                        }
                    }
                    
                    // If no team names found in meta, check pending assignments table
                    if (empty($team_names)) {
                        $pending_assignments_table = Club_Manager_Database::get_table_name('pending_trainer_assignments');
                        
                        if ($wpdb->get_var("SHOW TABLES LIKE '$pending_assignments_table'") === $pending_assignments_table) {
                            $assigned_teams = $wpdb->get_results($wpdb->prepare(
                                "SELECT t.name 
                                FROM $pending_assignments_table pa
                                INNER JOIN $teams_table t ON pa.team_id = t.id
                                WHERE pa.trainer_email = %s",
                                $email
                            ));
                            
                            foreach ($assigned_teams as $team) {
                                $team_names[] = $team->name;
                            }
                        }
                    }
                    
                    $invitations[] = array(
                        'id' => $inv_post->ID,
                        'email' => $email,
                        'team_name' => !empty($team_names) ? implode(', ', $team_names) : 'Unknown',
                        'team_names' => $team_names,
                        'team_id' => $inv_post->post_parent,
                        'created_at' => $inv_post->post_date,
                        'role' => isset($all_meta['_cm_role']) ? $all_meta['_cm_role'][0] : 'trainer'
                    );
                }
            }
        }
        
        wp_send_json_success($invitations);
    }
    
    /**
     * Get active trainers.
     */
    public function get_active_trainers() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get trainers for teams owned by user
        $trainers_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT tt.trainer_id, tt.role, tt.is_active, u.display_name, u.user_email as email,
                    um1.meta_value as first_name, um2.meta_value as last_name
            FROM $trainers_table tt
            INNER JOIN $teams_table t ON tt.team_id = t.id
            INNER JOIN {$wpdb->users} u ON tt.trainer_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um1 ON u.ID = um1.user_id AND um1.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = 'last_name'
            WHERE t.created_by = %d
            ORDER BY u.display_name",
            $user_id
        ));
        
        // Group teams by trainer
        $trainers = [];
        foreach ($trainers_data as $trainer) {
            $trainer_id = $trainer->trainer_id;
            
            if (!isset($trainers[$trainer_id])) {
                $trainers[$trainer_id] = [
                    'id' => $trainer_id,
                    'display_name' => $trainer->display_name,
                    'email' => $trainer->email,
                    'first_name' => $trainer->first_name,
                    'last_name' => $trainer->last_name,
                    'role' => $trainer->role,
                    'is_active' => $trainer->is_active,
                    'teams' => []
                ];
            }
            
            // Get teams for this trainer
            $trainer_teams = $wpdb->get_results($wpdb->prepare(
                "SELECT t.id, t.name 
                FROM $trainers_table tt
                INNER JOIN $teams_table t ON tt.team_id = t.id
                WHERE tt.trainer_id = %d AND t.created_by = %d",
                $trainer_id, $user_id
            ));
            
            $trainers[$trainer_id]['teams'] = $trainer_teams;
        }
        
        wp_send_json_success(array_values($trainers));
    }
    
    /**
     * Invite a trainer using official WC Teams system.
     */
    public function invite_trainer() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        if (!function_exists('wc_memberships_for_teams')) {
            wp_send_json_error('WooCommerce Teams for Memberships is required');
            return;
        }
        
        $email = $this->get_post_data('email', 'email');
        $team_ids = isset($_POST['teams']) ? array_map('intval', $_POST['teams']) : [];
        $role = $this->get_post_data('role');
        $message = $this->get_post_data('message', 'textarea');
        
        if (empty($email) || empty($team_ids)) {
            wp_send_json_error('Email and teams are required');
            return;
        }
        
        // Get the first WC Team where user is owner/manager
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
        if (empty($managed_teams)) {
            wp_send_json_error('Je hebt geen WooCommerce Team waar je trainers aan kunt toevoegen.');
            return;
        }
        
        $wc_team_id = $managed_teams[0]['team_id'];
        $wc_team = wc_memberships_for_teams_get_team($wc_team_id);
        
        if (!$wc_team) {
            wp_send_json_error('Geen geldige WooCommerce Team gevonden.');
            return;
        }
        
        try {
            // Get the invitations instance
            $invitations_instance = wc_memberships_for_teams()->get_invitations_instance();
            
            if (!$invitations_instance || !method_exists($invitations_instance, 'create_invitation')) {
                wp_send_json_error('Could not access invitations system');
                return;
            }
            
            // Create invitation using the official API
            $invitation = $invitations_instance->create_invitation(array(
                'team_id' => $wc_team->get_id(),
                'email' => $email,
                'sender_id' => $user_id,
                'role' => 'member' // WC Teams only supports 'member' role
            ));
            
            if (!$invitation || is_wp_error($invitation)) {
                $error_message = is_wp_error($invitation) ? $invitation->get_error_message() : 'Could not create invitation';
                wp_send_json_error($error_message);
                return;
            }
            
            // Store Club Manager specific data
            update_post_meta($invitation->get_id(), '_cm_team_ids', $team_ids);
            update_post_meta($invitation->get_id(), '_cm_role', $role);
            update_post_meta($invitation->get_id(), '_cm_message', $message);
            
            // IMPORTANT: Create pending assignments for each selected team
            global $wpdb;
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
            
            // Insert pending assignment for each team
            foreach ($team_ids as $team_id) {
                $wpdb->replace(
                    $pending_assignments_table,
                    [
                        'team_id' => $team_id,
                        'trainer_email' => $email,
                        'assigned_by' => $user_id,
                        'assigned_at' => current_time('mysql')
                    ],
                    ['%d', '%s', '%d', '%s']
                );
            }
            
            // Get team names for email
            $team_names = [];
            $teams_table = Club_Manager_Database::get_table_name('teams');
            foreach ($team_ids as $team_id) {
                $team_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT name FROM $teams_table WHERE id = %d",
                    $team_id
                ));
                if ($team_name) {
                    $team_names[] = $team_name;
                }
            }
            
            // Send custom email
            $this->send_trainer_invitation_email(
                $email,
                $invitation->get_token(),
                $user_id,
                $team_names,
                $message
            );
            
            wp_send_json_success([
                'message' => 'Invitation sent successfully',
                'invitation_id' => $invitation->get_id()
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Error creating invitation: ' . $e->getMessage());
        }
    }
    /**
     * Send custom trainer invitation email
     */
    private function send_trainer_invitation_email($email, $token, $inviter_id, $team_names, $message) {
        $inviter = get_user_by('id', $inviter_id);
        $club_name = get_bloginfo('name');
        
        // Get accept URL
        global $wpdb;
        $page_id = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_content LIKE '%[club_manager_accept_invitation]%' 
             AND post_status = 'publish' 
             AND post_type = 'page' 
             LIMIT 1"
        );
        
        if ($page_id && $token) {
            $accept_url = add_query_arg([
                'wc_invite' => $token
            ], get_permalink($page_id));
        } else {
            // Fallback
            $accept_url = home_url('/invitation/?wc_invite=' . $token);
        }
        
        // Build email subject
        $subject = sprintf('[%s] Trainer Invitation', $club_name);
        
        // Build professional HTML email
        $body = $this->get_trainer_invitation_email_template([
            'club_name' => $club_name,
            'inviter_name' => $inviter ? $inviter->display_name : 'Club Administrator',
            'team_names' => $team_names,
            'message' => $message,
            'accept_url' => $accept_url,
            'email' => $email
        ]);
        
        // Add headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $club_name . ' <' . get_option('admin_email') . '>'
        );
        
        // Send email
        $sent = wp_mail($email, $subject, $body, $headers);
        
        if (!$sent) {
            error_log('Club Manager: Failed to send invitation email to ' . $email);
        }
        
        return $sent;
    }
    
    /**
     * Get professional HTML email template for trainer invitation
     */
    private function get_trainer_invitation_email_template($data) {
        $club_name = esc_html($data['club_name']);
        $inviter_name = esc_html($data['inviter_name']);
        $team_names = $data['team_names'];
        $message = $data['message'];
        $accept_url = esc_url($data['accept_url']);
        
        // Build team list
        $team_list_html = '';
        if (!empty($team_names)) {
            $team_list_html = '<div style="margin: 20px 0;">';
            $team_list_html .= '<p style="margin: 0 0 10px 0; font-weight: 600; color: #374151;">You will have access to the following team(s):</p>';
            $team_list_html .= '<ul style="margin: 0; padding-left: 20px; color: #6B7280;">';
            foreach ($team_names as $team_name) {
                $team_list_html .= '<li style="margin: 5px 0;">' . esc_html($team_name) . '</li>';
            }
            $team_list_html .= '</ul>';
            $team_list_html .= '</div>';
        }
        
        // Build personal message (use default welcome message if empty or bulk import message)
        $message_html = '';
        $display_message = $message;
        
        // Replace bulk import message with professional welcome
        if (empty($message) || strpos($message, 'bulk import') !== false) {
            $display_message = 'We are excited to have you join our coaching team. Your expertise and dedication will be invaluable in helping our players develop their skills and achieve their potential.';
        }
        
        if (!empty($display_message)) {
            $message_html = '<div style="background-color: #F9FAFB; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #F97316;">';
            $message_html .= '<p style="margin: 0 0 10px 0; font-weight: 600; color: #374151;">Welcome Message:</p>';
            $message_html .= '<p style="margin: 0; color: #6B7280; font-style: italic;">' . esc_html($display_message) . '</p>';
            $message_html .= '</div>';
        }
        
        $html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Invitation - ' . $club_name . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #F3F4F6;">
    <div style="max-width: 600px; margin: 20px auto; background-color: #FFFFFF; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #F97316 0%, #EA580C 100%); padding: 40px 30px; text-align: center;">
            <h1 style="margin: 0; color: #FFFFFF; font-size: 28px; font-weight: 700;">
                Trainer Invitation
            </h1>
            <p style="margin: 10px 0 0 0; color: #FED7AA; font-size: 16px;">
                ' . $club_name . '
            </p>
        </div>
        
        <!-- Content -->
        <div style="padding: 40px 30px;">
            <h2 style="margin: 0 0 20px 0; color: #1F2937; font-size: 24px; font-weight: 600;">
                Hello!
            </h2>
            
            <p style="margin: 0 0 20px 0; color: #6B7280; font-size: 16px;">
                <strong>' . $inviter_name . '</strong> has invited you to become a trainer at <strong>' . $club_name . '</strong>.
            </p>
            
            ' . $team_list_html . '
            
            ' . $message_html . '
            
            <!-- Call to Action -->
            <div style="text-align: center; margin: 40px 0;">
                <a href="' . $accept_url . '" style="display: inline-block; background: linear-gradient(135deg, #F97316 0%, #EA580C 100%); color: #FFFFFF; text-decoration: none; padding: 16px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: all 0.2s;">
                    Accept Invitation
                </a>
            </div>
            
            <div style="background-color: #FEF3C7; border: 1px solid #F59E0B; border-radius: 8px; padding: 16px; margin: 30px 0;">
                <p style="margin: 0; color: #92400E; font-size: 14px;">
                    <strong>Important:</strong> This invitation will expire in 7 days. Please accept it as soon as possible.
                </p>
            </div>
            
            <p style="margin: 20px 0 0 0; color: #9CA3AF; font-size: 14px;">
                If you\'re having trouble with the button above, copy and paste the following link into your browser:
                <br><a href="' . $accept_url . '" style="color: #F97316; word-break: break-all;">' . $accept_url . '</a>
            </p>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #F9FAFB; padding: 30px; text-align: center; border-top: 1px solid #E5E7EB;">
            <p style="margin: 0; color: #6B7280; font-size: 14px;">
                Best regards,<br>
                <strong>' . $club_name . '</strong>
            </p>
            <p style="margin: 15px 0 0 0; color: #9CA3AF; font-size: 12px;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Cancel a pending invitation.
     */
    public function cancel_invitation() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $invitation_id = $this->get_post_data('invitation_id', 'int');
        
        if (!function_exists('wc_memberships_for_teams')) {
            wp_send_json_error('WooCommerce Teams for Memberships is required');
            return;
        }
        
        try {
            // Get the invitations instance
            $invitations_instance = wc_memberships_for_teams()->get_invitations_instance();
            
            if (!$invitations_instance || !method_exists($invitations_instance, 'get_invitation')) {
                wp_send_json_error('Could not access invitations system');
                return;
            }
            
            // Get the invitation
            $invitation = $invitations_instance->get_invitation($invitation_id);
            
            if (!$invitation) {
                wp_send_json_error('Invitation not found');
                return;
            }
            
            // Verify permission
            $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($user_id);
            $allowed_team_ids = array_column($managed_teams, 'team_id');
            
            if (!in_array($invitation->get_team_id(), $allowed_team_ids)) {
                wp_send_json_error('Unauthorized access');
                return;
            }
            
            // Get the email from the invitation before cancelling
            $invitation_email = null;
            $meta_data = get_post_meta($invitation_id);
            if (isset($meta_data['_email'][0])) {
                $invitation_email = $meta_data['_email'][0];
            } elseif (isset($meta_data['_recipient_email'][0])) {
                $invitation_email = $meta_data['_recipient_email'][0];
            }
            
            // Cancel the invitation
            $invitation->cancel();
            
            // IMPORTANT: Also remove any pending assignments for this email
            if ($invitation_email) {
                global $wpdb;
                $pending_assignments_table = Club_Manager_Database::get_table_name('pending_trainer_assignments');
                
                // Check if table exists
                if ($wpdb->get_var("SHOW TABLES LIKE '$pending_assignments_table'") === $pending_assignments_table) {
                    $wpdb->delete(
                        $pending_assignments_table,
                        ['trainer_email' => $invitation_email],
                        ['%s']
                    );
                }
            }
            
            wp_send_json_success(['message' => 'Invitation cancelled and pending assignments removed']);
            
        } catch (Exception $e) {
            wp_send_json_error('Error cancelling invitation: ' . $e->getMessage());
        }
    }
    
    /**
     * Update trainer teams and role.
     */
    public function update_trainer() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $trainer_id = $this->get_post_data('trainer_id', 'int');
        $team_ids = isset($_POST['teams']) ? array_map('intval', $_POST['teams']) : [];
        $role = $this->get_post_data('role');
        
        if (empty($trainer_id) || empty($team_ids)) {
            wp_send_json_error('Trainer ID and teams are required');
            return;
        }
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get all teams owned by current user
        $owned_teams = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $teams_table WHERE created_by = %d",
            $user_id
        ));
        
        if (empty($owned_teams)) {
            wp_send_json_error('No teams found');
            return;
        }
        
        // Verify all selected teams are owned by user
        foreach ($team_ids as $team_id) {
            if (!in_array($team_id, $owned_teams)) {
                wp_send_json_error('Unauthorized access to team');
                return;
            }
        }
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Remove trainer from all teams owned by user
            $wpdb->query($wpdb->prepare(
                "DELETE tt FROM $trainers_table tt
                INNER JOIN $teams_table t ON tt.team_id = t.id
                WHERE tt.trainer_id = %d AND t.created_by = %d",
                $trainer_id, $user_id
            ));
            
            // Add trainer to selected teams
            foreach ($team_ids as $team_id) {
                $wpdb->insert(
                    $trainers_table,
                    [
                        'team_id' => $team_id,
                        'trainer_id' => $trainer_id,
                        'role' => $role,
                        'is_active' => 1,
                        'added_by' => $user_id,
                        'added_at' => current_time('mysql')
                    ],
                    ['%d', '%d', '%s', '%d', '%d', '%s']
                );
            }
            
            $wpdb->query('COMMIT');
            
            wp_send_json_success(['message' => 'Trainer updated successfully']);
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error('Error updating trainer: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove a trainer.
     */
    public function remove_trainer() {
        $user_id = $this->verify_request();
        
        if (!class_exists('Club_Manager_Teams_Helper') || !Club_Manager_Teams_Helper::can_view_club_teams($user_id)) {
            wp_send_json_error('Unauthorized access');
            return;
        }
        
        $trainer_id = $this->get_post_data('trainer_id', 'int');
        
        global $wpdb;
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get all teams this trainer is part of that belong to current user
        $trainer_teams = $wpdb->get_results($wpdb->prepare(
            "SELECT tt.*, t.created_by
            FROM $trainers_table tt
            INNER JOIN $teams_table t ON tt.team_id = t.id
            WHERE tt.trainer_id = %d AND t.created_by = %d",
            $trainer_id, $user_id
        ));
        
        // Remove from WooCommerce team
        if (!empty($trainer_teams)) {
            $this->remove_from_wc_team($trainer_id, $user_id);
        }
        
        // Remove trainer from Club Manager tables
        $wpdb->query($wpdb->prepare(
            "DELETE tt FROM $trainers_table tt
            INNER JOIN $teams_table t ON tt.team_id = t.id
            WHERE tt.trainer_id = %d AND t.created_by = %d",
            $trainer_id, $user_id
        ));
        
        wp_send_json_success(['message' => 'Trainer removed successfully']);
    }
    
    /**
     * Remove user from WooCommerce team.
     */
    private function remove_from_wc_team($trainer_id, $owner_id) {
        if (!function_exists('wc_memberships_for_teams')) {
            return false;
        }
        
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($owner_id);
        if (empty($managed_teams)) {
            return false;
        }
        
        foreach ($managed_teams as $team_data) {
            $wc_team = wc_memberships_for_teams_get_team($team_data['team_id']);
            
            if (!$wc_team) {
                continue;
            }
            
            $member = $wc_team->get_member($trainer_id);
            if ($member && method_exists($member, 'delete')) {
                try {
                    $member->delete();
                    return true;
                } catch (Exception $e) {
                    error_log('Club Manager: Failed to remove member from WC Team: ' . $e->getMessage());
                }
            }
        }
        
        return false;
    }
}