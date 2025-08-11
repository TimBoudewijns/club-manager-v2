<?php

/**
 * Handle trainer invitation acceptance with integrated login/register
 */
class Club_Manager_Trainer_Invitation_Handler {
    
    /**
     * Initialize the invitation handler
     */
    public function init() {
        add_action('init', array($this, 'check_invitation_token'));
        add_shortcode('club_manager_accept_invitation', array($this, 'render_accept_invitation'));
        add_action('wp_ajax_nopriv_cm_check_email', array($this, 'ajax_check_email'));
        add_action('wp_ajax_nopriv_cm_login_trainer', array($this, 'ajax_login_trainer'));
        add_action('wp_ajax_nopriv_cm_register_trainer', array($this, 'ajax_register_trainer'));
        add_action('wp_ajax_cm_accept_trainer_invitation', array($this, 'ajax_accept_invitation'));
    }

    /**
     * Process pending trainer assignments after invitation acceptance
     */
    private function process_pending_assignments($user_id, $email) {
        global $wpdb;
        
        $pending_table = Club_Manager_Database::get_table_name('pending_trainer_assignments');
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$pending_table'") !== $pending_table) {
            return;
        }
        
        // Get pending assignments for this email
        $pending_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $pending_table WHERE trainer_email = %s",
            $email
        ));
        
        if (empty($pending_assignments)) {
            return;
        }
        
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        foreach ($pending_assignments as $assignment) {
            // Add trainer to team
            $wpdb->insert(
                $trainers_table,
                [
                    'team_id' => $assignment->team_id,
                    'trainer_id' => $user_id,
                    'role' => 'trainer',
                    'is_active' => 1,
                    'added_by' => $assignment->assigned_by,
                    'added_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%d', '%d', '%s']
            );
            
            // Remove pending assignment
            $wpdb->delete(
                $pending_table,
                ['id' => $assignment->id],
                ['%d']
            );
        }
    }
    
    /**
     * Check if invitation token is present in URL
     */
    public function check_invitation_token() {
        // Check for WC Teams invitation token
        if (isset($_GET['wc_invite']) && !isset($_GET['cm_processed'])) {
            $token = sanitize_text_field($_GET['wc_invite']);
            
            // Store token in session
            if (!session_id()) {
                session_start();
            }
            $_SESSION['wc_invitation_token'] = $token;
            
            // Find the page with our shortcode
            global $wpdb;
            $page_id = $wpdb->get_var(
                "SELECT ID FROM {$wpdb->posts} 
                 WHERE post_content LIKE '%[club_manager_accept_invitation]%' 
                 AND post_status = 'publish' 
                 AND post_type = 'page' 
                 LIMIT 1"
            );
            
            if ($page_id && !is_page($page_id)) {
                // Redirect to the invitation page
                wp_redirect(add_query_arg(array(
                    'wc_invite' => $token,
                    'cm_processed' => '1'
                ), get_permalink($page_id)));
                exit;
            }
        }
    }
    
    /**
     * Render the invitation acceptance page
     */
    public function render_accept_invitation() {
        $token = isset($_GET['wc_invite']) ? sanitize_text_field($_GET['wc_invite']) : '';
        
        if (empty($token)) {
            if (!session_id()) {
                session_start();
            }
            $token = isset($_SESSION['wc_invitation_token']) ? $_SESSION['wc_invitation_token'] : '';
        }
        
        if (empty($token)) {
            return '<div class="cm-invitation-error">
                <p>No valid invitation link found.</p>
            </div>';
        }
        
        // Get WC Teams invitation
        if (!function_exists('wc_memberships_for_teams')) {
            return '<div class="cm-invitation-error">
                <p>WooCommerce Teams for Memberships is required.</p>
            </div>';
        }
        
        // Get invitation by token
        $invitation = $this->get_invitation_by_token($token);
        
        if (!$invitation) {
            return '<div class="cm-invitation-error">
                <p>This invitation is invalid or has already been used.</p>
            </div>';
        }
        
        // Get team and inviter info
        $team = $invitation->get_team();
        $inviter_id = $invitation->get_sender_id();
        $inviter = get_user_by('id', $inviter_id);
        $message = get_post_meta($invitation->get_id(), '_cm_message', true);
        $cm_team_ids = get_post_meta($invitation->get_id(), '_cm_team_ids', true);
        
        // Get CM team names
        $team_names = [];
        if ($cm_team_ids && is_array($cm_team_ids)) {
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
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

        // Note: Pending assignments will be processed after successful login/registration 
        
        // Get club name from inviter's WooCommerce Team
        $club_name = get_bloginfo('name'); // Default fallback
        if ($inviter && class_exists('Club_Manager_Teams_Helper') && function_exists('wc_memberships_for_teams')) {
            $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($inviter->ID);
            if (!empty($managed_teams)) {
                $wc_team_id = $managed_teams[0]['team_id'];
                $wc_team = wc_memberships_for_teams_get_team($wc_team_id);
                if ($wc_team && is_object($wc_team) && method_exists($wc_team, 'get_name')) {
                    $club_name = $wc_team->get_name();
                }
            }
        }
        
        $invitation_data = (object) array(
            'email' => $invitation->get_email(),
            'team_name' => !empty($team_names) ? implode(', ', $team_names) : ($team ? $team->get_name() : 'Unknown Team'),
            'team_names' => $team_names,
            'inviter_name' => $inviter ? $inviter->display_name : 'Someone',
            'message' => $message,
            'club_name' => $club_name
        );
        
        // Check if user is already logged in
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if ($current_user->user_email === $invitation->get_email()) {
                return $this->render_logged_in_acceptance($invitation, $token);
            } else {
                return '<div class="cm-invitation-error">
                    <p>You are logged in with a different email address (' . esc_html($current_user->user_email) . ').</p>
                    <p>This invitation was sent to: ' . esc_html($invitation->get_email()) . '</p>
                    <p><a href="' . wp_logout_url(add_query_arg('wc_invite', $token, get_permalink())) . '">Logout and try again</a></p>
                </div>';
            }
        }
        
        // Enqueue scripts
        wp_enqueue_script('cm-invitation', CLUB_MANAGER_PLUGIN_URL . 'assets/js/invitation.js', array('jquery'), CLUB_MANAGER_VERSION, true);
        wp_localize_script('cm-invitation', 'cm_invitation', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cm_invitation_nonce'),
            'token' => $token
        ));
        
        ob_start();
        ?>
        <div class="cm-invitation-wrapper">
            <div class="cm-invitation-container">
                <!-- Header -->
                <div class="cm-invitation-header">
                    <h1>Trainer Invitation</h1>
                    <p class="cm-invitation-subtitle">
                        You have been invited by <strong><?php echo esc_html($invitation_data->inviter_name); ?></strong> 
                        to become a trainer at <strong><?php echo esc_html($invitation_data->club_name); ?></strong>
                    </p>
                </div>
                
                <?php if (!empty($team_names)): ?>
                    <div class="cm-invitation-teams">
                        <h3>You will have access to the following team(s):</h3>
                        <ul>
                            <?php foreach ($team_names as $team_name): ?>
                                <li><?php echo esc_html($team_name); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($invitation_data->message)): ?>
                    <div class="cm-invitation-message">
                        <h3>Personal Message:</h3>
                        <p><?php echo nl2br(esc_html($invitation_data->message)); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Dynamic content area -->
                <div id="cm-invitation-content">
                    <!-- Step 1: Email check -->
                    <div id="cm-step-email" class="cm-step active">
                        <h2>Step 1: Confirm your email address</h2>
                        <p>This invitation was sent to:</p>
                        <div class="cm-email-display">
                            <?php echo esc_html($invitation_data->email); ?>
                        </div>
                        <button id="cm-check-email" class="cm-btn cm-btn-primary" data-email="<?php echo esc_attr($invitation_data->email); ?>">
                            This is my email address →
                        </button>
                    </div>
                    
                    <!-- Step 2: Login -->
                    <div id="cm-step-login" class="cm-step" style="display: none;">
                        <h2>Step 2: Login</h2>
                        <p>We found an account with this email address. Please log in to continue.</p>
                        
                        <form id="cm-login-form" class="cm-form">
                            <div class="cm-form-group">
                                <label for="cm-login-email">Email Address</label>
                                <input type="email" id="cm-login-email" value="<?php echo esc_attr($invitation->get_email()); ?>" readonly>
                            </div>
                            
                            <div class="cm-form-group">
                                <label for="cm-login-password">Password</label>
                                <input type="password" id="cm-login-password" required>
                            </div>
                            
                            <div class="cm-form-actions">
                                <button type="submit" class="cm-btn cm-btn-primary">Login</button>
                                <a href="<?php echo wp_lostpassword_url(); ?>" class="cm-link">Forgot password?</a>
                            </div>
                        </form>
                        
                        <div class="cm-form-footer">
                            <button class="cm-btn-link" onclick="location.reload()">← Back</button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Register -->
                    <div id="cm-step-register" class="cm-step" style="display: none;">
                        <h2>Step 2: Create Account</h2>
                        <p>You don't have an account yet. Create an account to accept the invitation.</p>
                        
                        <form id="cm-register-form" class="cm-form">
                            <div class="cm-form-row">
                                <div class="cm-form-group">
                                    <label for="cm-register-firstname">First Name</label>
                                    <input type="text" id="cm-register-firstname" required>
                                </div>
                                
                                <div class="cm-form-group">
                                    <label for="cm-register-lastname">Last Name</label>
                                    <input type="text" id="cm-register-lastname" required>
                                </div>
                            </div>
                            
                            <div class="cm-form-group">
                                <label for="cm-register-email">Email Address</label>
                                <input type="email" id="cm-register-email" value="<?php echo esc_attr($invitation->get_email()); ?>" readonly>
                            </div>
                            
                            <div class="cm-form-group">
                                <label for="cm-register-password">Password</label>
                                <input type="password" id="cm-register-password" required minlength="8">
                                <small>Minimum 8 characters</small>
                            </div>
                            
                            <div class="cm-form-group">
                                <label for="cm-register-password-confirm">Confirm Password</label>
                                <input type="password" id="cm-register-password-confirm" required>
                            </div>
                            
                            <div class="cm-form-actions">
                                <button type="submit" class="cm-btn cm-btn-primary">Create Account</button>
                            </div>
                        </form>
                        
                        <div class="cm-form-footer">
                            <button class="cm-btn-link" onclick="location.reload()">← Back</button>
                        </div>
                    </div>
                    
                    <!-- Loading state -->
                    <div id="cm-loading" class="cm-loading" style="display: none;">
                        <div class="cm-spinner"></div>
                        <p>Please wait...</p>
                    </div>
                    
                    <!-- Error messages -->
                    <div id="cm-error" class="cm-error" style="display: none;"></div>
                    
                    <!-- Success message -->
                    <div id="cm-success" class="cm-success" style="display: none;"></div>
                </div>
                
                <!-- Info box -->
                <div class="cm-info-box">
                    <h3>What happens after acceptance?</h3>
                    <ul>
                        <li>You will be added as a trainer to the selected teams</li>
                        <li>You will gain access to the Club Manager dashboard</li>
                        <li>You can view and evaluate players</li>
                        <li>You will receive team membership benefits</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .cm-invitation-wrapper {
            max-width: 600px;
            margin: 40px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .cm-invitation-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .cm-invitation-header {
            background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .cm-invitation-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
        }
        
        .cm-invitation-subtitle {
            margin: 0;
            font-size: 16px;
            opacity: 0.95;
        }
        
        .cm-invitation-teams {
            background: #fef3c7;
            padding: 20px 30px;
            border-bottom: 1px solid #fcd34d;
        }
        
        .cm-invitation-teams h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #92400e;
        }
        
        .cm-invitation-teams ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .cm-invitation-teams li {
            color: #78350f;
            margin-bottom: 5px;
        }
        
        .cm-invitation-message {
            background: #fef3c7;
            padding: 20px 30px;
            border-bottom: 1px solid #fcd34d;
        }
        
        .cm-invitation-message h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #92400e;
        }
        
        .cm-invitation-message p {
            margin: 0;
            color: #78350f;
        }
        
        #cm-invitation-content {
            padding: 30px;
        }
        
        .cm-step h2 {
            margin: 0 0 15px 0;
            font-size: 22px;
            color: #1f2937;
        }
        
        .cm-email-display {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
            color: #1f2937;
        }
        
        .cm-btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .cm-btn-primary {
            background: linear-gradient(135deg, #F97316 0%, #EA580C 100%);
            color: white;
        }
        
        .cm-btn-primary:hover {
            background: linear-gradient(135deg, #EA580C 0%, #DC2626 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
        }
        
        .cm-btn-link {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
            padding: 0;
        }
        
        .cm-form {
            margin-top: 20px;
        }
        
        .cm-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .cm-form-group {
            margin-bottom: 20px;
        }
        
        .cm-form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .cm-form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        
        .cm-form-group input:focus {
            outline: none;
            border-color: #F97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }
        
        .cm-form-group input[readonly] {
            background: #f9fafb;
            color: #6b7280;
        }
        
        .cm-form-group small {
            display: block;
            margin-top: 4px;
            color: #6b7280;
            font-size: 13px;
        }
        
        .cm-form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 25px;
        }
        
        .cm-form-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .cm-link {
            color: #F97316;
            text-decoration: none;
            font-size: 14px;
        }
        
        .cm-link:hover {
            text-decoration: underline;
        }
        
        .cm-info-box {
            background: #f9fafb;
            padding: 25px 30px;
            border-top: 1px solid #e5e7eb;
        }
        
        .cm-info-box h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #1f2937;
        }
        
        .cm-info-box ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .cm-info-box li {
            margin-bottom: 8px;
            color: #4b5563;
        }
        
        .cm-loading {
            text-align: center;
            padding: 40px;
        }
        
        .cm-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #f3f4f6;
            border-top-color: #F97316;
            border-radius: 50%;
            animation: cm-spin 0.8s linear infinite;
        }
        
        @keyframes cm-spin {
            to { transform: rotate(360deg); }
        }
        
        .cm-error {
            background: #fee;
            color: #c00;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .cm-success {
            background: #d1fae5;
            color: #065f46;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
        
        .cm-success h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        
        @media (max-width: 640px) {
            .cm-invitation-wrapper {
                margin: 20px;
            }
            
            .cm-form-row {
                grid-template-columns: 1fr;
            }
            
            .cm-form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .cm-btn {
                width: 100%;
                text-align: center;
            }
        }
        </style>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Get invitation by token
     */
    private function get_invitation_by_token($token) {
        global $wpdb;
        
        // Find invitation post by token
        $invitation_post = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->posts} 
            WHERE post_type = 'wc_team_invitation' 
            AND post_password = %s 
            AND post_status = 'wcmti-pending'
            LIMIT 1",
            $token
        ));
        
        if (!$invitation_post) {
            return false;
        }
        
        // Get the invitations instance
        $invitations_instance = wc_memberships_for_teams()->get_invitations_instance();
        
        if (!$invitations_instance || !method_exists($invitations_instance, 'get_invitation')) {
            return false;
        }
        
        return $invitations_instance->get_invitation($invitation_post->ID);
    }
    
    /**
     * Render acceptance form for logged in users
     */
    private function render_logged_in_acceptance($invitation, $token) {
        $team = $invitation->get_team();
        $inviter_id = $invitation->get_sender_id();
        $inviter = get_user_by('id', $inviter_id);
        $message = get_post_meta($invitation->get_id(), '_cm_message', true);
        $cm_team_ids = get_post_meta($invitation->get_id(), '_cm_team_ids', true);
        
        // Get CM team names
        $team_names = [];
        if ($cm_team_ids && is_array($cm_team_ids)) {
            global $wpdb;
            $teams_table = Club_Manager_Database::get_table_name('teams');
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
        
        // Get club name from inviter's WooCommerce Team
        $club_name = get_bloginfo('name'); // Default fallback
        if ($inviter && class_exists('Club_Manager_Teams_Helper') && function_exists('wc_memberships_for_teams')) {
            $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($inviter->ID);
            if (!empty($managed_teams)) {
                $wc_team_id = $managed_teams[0]['team_id'];
                $wc_team = wc_memberships_for_teams_get_team($wc_team_id);
                if ($wc_team && is_object($wc_team) && method_exists($wc_team, 'get_name')) {
                    $club_name = $wc_team->get_name();
                }
            }
        }
        
        ob_start();
        ?>
        <div class="cm-invitation-wrapper">
            <div class="cm-invitation-container">
                <div class="cm-invitation-header">
                    <h1>Accept Invitation</h1>
                </div>
                
                <div style="padding: 30px;">
                    <p>You have been invited by <strong><?php echo $inviter ? esc_html($inviter->display_name) : 'Someone'; ?></strong> 
                    to become a trainer at <strong><?php echo esc_html($club_name); ?></strong>.</p>
                    
                    <?php if (!empty($team_names)): ?>
                        <div style="margin: 20px 0;">
                            <h3>You will have access to the following team(s):</h3>
                            <ul>
                                <?php foreach ($team_names as $team_name): ?>
                                    <li><?php echo esc_html($team_name); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($message)): ?>
                        <div class="cm-invitation-message" style="margin: 20px 0;">
                            <h3>Personal Message:</h3>
                            <p><?php echo nl2br(esc_html($message)); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" style="margin-top: 30px;">
                        <?php wp_nonce_field('accept_trainer_invitation', 'invitation_nonce'); ?>
                        <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                        
                        <div style="display: flex; gap: 15px;">
                            <button type="submit" name="accept_invitation" value="1" class="cm-btn cm-btn-primary">
                                Accept
                            </button>
                            <button type="submit" name="decline_invitation" value="1" class="cm-btn" style="background: #dc2626; color: white;">
                                Decline
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        
        // Handle form submission
        if (isset($_POST['accept_invitation']) && wp_verify_nonce($_POST['invitation_nonce'], 'accept_trainer_invitation')) {
            $result = $this->process_invitation_for_user(get_current_user_id(), $token);
            if ($result['success']) {
                return '<div class="cm-success">
                    <h3>Success!</h3>
                    <p>' . esc_html($result['message']) . '</p>
                    <p style="margin-top: 15px;">
                        <a href="' . $this->get_dashboard_url() . '" class="cm-btn cm-btn-primary">
                            Go to Club Manager Dashboard
                        </a>
                    </p>
                </div>';
            } else {
                return '<div class="cm-error">
                    <p>' . esc_html($result['message']) . '</p>
                </div>';
            }
        }
        
        if (isset($_POST['decline_invitation']) && wp_verify_nonce($_POST['invitation_nonce'], 'accept_trainer_invitation')) {
            return $this->process_invitation_decline($invitation);
        }
        
        return ob_get_clean();
    }
    
    /**
     * AJAX: Check if email exists
     */
    public function ajax_check_email() {
        check_ajax_referer('cm_invitation_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $user = get_user_by('email', $email);
        
        wp_send_json_success(array(
            'exists' => $user ? true : false
        ));
    }
    
    /**
     * AJAX: Login trainer
     */
    public function ajax_login_trainer() {
        check_ajax_referer('cm_invitation_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $token = sanitize_text_field($_POST['token']);
        
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error('Incorrect password. Please try again.');
        }
        
        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        // Process invitation
        $result = $this->process_invitation_for_user($user->ID, $token);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'redirect' => $this->get_dashboard_url()
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * AJAX: Register trainer
     */
    public function ajax_register_trainer() {
        check_ajax_referer('cm_invitation_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $firstname = sanitize_text_field($_POST['firstname']);
        $lastname = sanitize_text_field($_POST['lastname']);
        $token = sanitize_text_field($_POST['token']);
        
        // Create user
        $user_id = wp_create_user($email, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error('Could not create account. ' . $user_id->get_error_message());
        }
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $firstname);
        update_user_meta($user_id, 'last_name', $lastname);
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => $firstname . ' ' . $lastname
        ));
        
        // Log the user in
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        // Process invitation
        $result = $this->process_invitation_for_user($user_id, $token);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'redirect' => $this->get_dashboard_url()
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Process invitation for a specific user
     */
    private function process_invitation_for_user($user_id, $token) {
        if (!function_exists('wc_memberships_for_teams')) {
            return array(
                'success' => false,
                'message' => 'WooCommerce Teams for Memberships is required.'
            );
        }
        
        // Get invitation
        $invitation = $this->get_invitation_by_token($token);
        
        if (!$invitation) {
            return array(
                'success' => false,
                'message' => 'Invitation not found.'
            );
        }
        
        if ($invitation->get_status() !== 'pending') {
            return array(
                'success' => false,
                'message' => 'This invitation has already been accepted or cancelled.'
            );
        }
        
        // Accept the WC Teams invitation
        try {
            $team_member = $invitation->accept($user_id, true); // true = add member
            
            if (!$team_member) {
                throw new Exception('Could not create team member');
            }
            
            // Get team info
            $team = $invitation->get_team();
            $team_name = $team ? $team->get_name() : 'Unknown Team';
            
            // Process any pending assignments for this email
            $user_email = get_user_by('id', $user_id)->user_email;
            $this->process_pending_assignments($user_id, $user_email);
            
            // Add to Club Manager trainer table for each selected team
            $cm_team_ids = get_post_meta($invitation->get_id(), '_cm_team_ids', true);
            $role = get_post_meta($invitation->get_id(), '_cm_role', true) ?: 'trainer';
            $inviter_id = $invitation->get_sender_id();
            
            if ($cm_team_ids && is_array($cm_team_ids)) {
                $teams_added = [];
                foreach ($cm_team_ids as $cm_team_id) {
                    if ($this->add_trainer_to_team($cm_team_id, $user_id, $role, $inviter_id)) {
                        // Get team name for the notification
                        global $wpdb;
                        $teams_table = Club_Manager_Database::get_table_name('teams');
                        $team_name = $wpdb->get_var($wpdb->prepare(
                            "SELECT name FROM $teams_table WHERE id = %d",
                            $cm_team_id
                        ));
                        if ($team_name) {
                            $teams_added[] = $team_name;
                        }
                    }
                }
                
                // Send notification with all teams
                if (!empty($teams_added) && $inviter_id) {
                    $this->send_acceptance_notification($inviter_id, $user_id, $teams_added);
                }
            }
            
            return array(
                'success' => true,
                'message' => 'You have been successfully added to the team!'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Process invitation decline
     */
    private function process_invitation_decline($invitation) {
        try {
            $invitation->cancel();
            
            return '<div class="cm-success">
                <h3>Invitation Declined</h3>
                <p>You have declined the invitation to become a trainer.</p>
            </div>';
        } catch (Exception $e) {
            return '<div class="cm-error">
                <p>An error occurred while declining the invitation.</p>
            </div>';
        }
    }
    
    /**
     * Add trainer to team
     */
    private function add_trainer_to_team($team_id, $user_id, $role, $invited_by) {
        global $wpdb;
        
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        
        // Check if already a trainer
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $trainers_table WHERE team_id = %d AND trainer_id = %d",
            $team_id, $user_id
        ));
        
        if (!$existing) {
            return $wpdb->insert(
                $trainers_table,
                [
                    'team_id' => $team_id,
                    'trainer_id' => $user_id,
                    'role' => $role,
                    'is_active' => 1,
                    'added_by' => $invited_by,
                    'added_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%d', '%d', '%s']
            );
        }
        
        return true;
    }
    
    /**
     * Send acceptance notification
     */
    private function send_acceptance_notification($inviter_id, $trainer_id, $teams) {
        $inviter = get_user_by('id', $inviter_id);
        $trainer = get_user_by('id', $trainer_id);
        
        if (!$inviter || !$trainer) {
            return;
        }
        
        $subject = '[Dutch Field Hockey Drills] Trainer invitation accepted';
        
        $message = sprintf(
           "Hello %s,\n\n%s has accepted your invitation to become a trainer for the following teams:\n\n%s\n\nThey now have access to the Club Manager dashboard and can view/evaluate players in these teams.\n\nBest regards,\n%s",
           $inviter->display_name,
           $trainer->display_name,
           implode("\n", array_map(function($team) { return "- " . $team; }, $teams)),
           get_bloginfo('name')
       );
       
       wp_mail($inviter->user_email, $subject, $message);
   }
   
   /**
    * Get the correct dashboard URL
    */
   private function get_dashboard_url() {
       global $wpdb;
       
       // Find the page with [club_manager] shortcode
       $page_id = $wpdb->get_var(
           "SELECT ID FROM {$wpdb->posts} 
            WHERE post_content LIKE '%[club_manager]%' 
            AND post_status = 'publish' 
            AND post_type = 'page' 
            LIMIT 1"
       );
       
       if ($page_id) {
           return get_permalink($page_id);
       }
       
       // Check for common dashboard page slugs
       $common_slugs = ['club-manager', 'dashboard', 'team-manager', 'manager'];
       foreach ($common_slugs as $slug) {
           $page = get_page_by_path($slug);
           if ($page && $page->post_status === 'publish') {
               return get_permalink($page->ID);
           }
       }
       
       // If user is logged in, try to find any Club Manager related page
       if (is_user_logged_in()) {
           // Look for pages that might contain club manager content
           $pages = get_pages(array(
               'meta_query' => array(
                   array(
                       'key' => '_wp_page_template',
                       'compare' => 'EXISTS'
                   )
               ),
               'post_status' => 'publish'
           ));
           
           foreach ($pages as $page) {
               if (strpos(strtolower($page->post_content), 'club') !== false || 
                   strpos(strtolower($page->post_title), 'manager') !== false) {
                   return get_permalink($page->ID);
               }
           }
       }
       
       // Ultimate fallback - go to home page
       return home_url();
   }
}