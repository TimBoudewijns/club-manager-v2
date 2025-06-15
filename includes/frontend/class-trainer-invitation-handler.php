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
                <p>Geen geldige uitnodigingslink gevonden.</p>
            </div>';
        }
        
        // Get WC Teams invitation
        if (!function_exists('wc_memberships_for_teams_get_invitation_by_token')) {
            return '<div class="cm-invitation-error">
                <p>WooCommerce Teams for Memberships is vereist.</p>
            </div>';
        }
        
        $invitation = wc_memberships_for_teams_get_invitation_by_token($token);
        
        if (!$invitation || $invitation->get_status() !== 'pending') {
            return '<div class="cm-invitation-error">
                <p>Deze uitnodiging is al geaccepteerd of niet meer geldig.</p>
            </div>';
        }
        
        // Get team and inviter info
        $team = $invitation->get_team();
        $inviter_id = get_post_meta($invitation->get_id(), '_sender_id', true);
        $inviter = get_user_by('id', $inviter_id);
        $message = get_post_meta($invitation->get_id(), '_cm_message', true);
        
        $invitation_data = (object) array(
            'email' => $invitation->get_email(),
            'team_name' => $team ? $team->get_name() : 'Unknown Team',
            'inviter_name' => $inviter ? $inviter->display_name : 'Someone',
            'message' => $message
        );
        
        // Check if user is already logged in
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if ($current_user->user_email === $invitation->get_email()) {
                return $this->render_logged_in_acceptance($invitation, $token);
            } else {
                return '<div class="cm-invitation-error">
                    <p>Je bent ingelogd met een ander e-mailadres (' . esc_html($current_user->user_email) . ').</p>
                    <p>Deze uitnodiging is verzonden naar: ' . esc_html($invitation->get_email()) . '</p>
                    <p><a href="' . wp_logout_url(add_query_arg('wc_invite', $token, get_permalink())) . '">Uitloggen en opnieuw proberen</a></p>
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
                    <h1>Uitnodiging voor Trainer</h1>
                    <p class="cm-invitation-subtitle">
                        Je bent uitgenodigd door <strong><?php echo esc_html($invitation->inviter_name); ?></strong> 
                        om trainer te worden bij <strong><?php echo esc_html($invitation->team_name); ?></strong>
                    </p>
                </div>
                
                <?php if (!empty($invitation->message)): ?>
                    <div class="cm-invitation-message">
                        <h3>Persoonlijk bericht:</h3>
                        <p><?php echo nl2br(esc_html($invitation->message)); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Dynamic content area -->
                <div id="cm-invitation-content">
                    <!-- Step 1: Email check -->
                    <div id="cm-step-email" class="cm-step active">
                        <h2>Stap 1: Bevestig je e-mailadres</h2>
                        <p>Deze uitnodiging is verzonden naar:</p>
                        <div class="cm-email-display">
                            <?php echo esc_html($invitation_data->email); ?>
                        </div>
                        <button id="cm-check-email" class="cm-btn cm-btn-primary" data-email="<?php echo esc_attr($invitation_data->email); ?>">>
                            Dit is mijn e-mailadres →
                        </button>
                    </div>
                    
                    <!-- Step 2: Login -->
                    <div id="cm-step-login" class="cm-step" style="display: none;">
                        <h2>Stap 2: Inloggen</h2>
                        <p>We hebben een account gevonden met dit e-mailadres. Log in om door te gaan.</p>
                        
                        <form id="cm-login-form" class="cm-form">
                            <div class="cm-form-group">
                                <label for="cm-login-email">E-mailadres</label>
                                <input type="email" id="cm-login-email" value="<?php echo esc_attr($invitation->email); ?>" readonly>
                            </div>
                            
                            <div class="cm-form-group">
                                <label for="cm-login-password">Wachtwoord</label>
                                <input type="password" id="cm-login-password" required>
                            </div>
                            
                            <div class="cm-form-actions">
                                <button type="submit" class="cm-btn cm-btn-primary">Inloggen</button>
                                <a href="<?php echo wp_lostpassword_url(); ?>" class="cm-link">Wachtwoord vergeten?</a>
                            </div>
                        </form>
                        
                        <div class="cm-form-footer">
                            <button class="cm-btn-link" onclick="location.reload()">← Terug</button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Register -->
                    <div id="cm-step-register" class="cm-step" style="display: none;">
                        <h2>Stap 2: Account aanmaken</h2>
                        <p>Je hebt nog geen account. Maak een account aan om de uitnodiging te accepteren.</p>
                        
                        <form id="cm-register-form" class="cm-form">
                            <div class="cm-form-row">
                                <div class="cm-form-group">
                                    <label for="cm-register-firstname">Voornaam</label>
                                    <input type="text" id="cm-register-firstname" required>
                                </div>
                                
                                <div class="cm-form-group">
                                    <label for="cm-register-lastname">Achternaam</label>
                                    <input type="text" id="cm-register-lastname" required>
                                </div>
                            </div>
                            
                            <div class="cm-form-group">
                                <label for="cm-register-email">E-mailadres</label>
                                <input type="email" id="cm-register-email" value="<?php echo esc_attr($invitation->email); ?>" readonly>
                            </div>
                            
                            <div class="cm-form-group">
                                <label for="cm-register-password">Wachtwoord</label>
                                <input type="password" id="cm-register-password" required minlength="8">
                                <small>Minimaal 8 karakters</small>
                            </div>
                            
                            <div class="cm-form-group">
                                <label for="cm-register-password-confirm">Bevestig wachtwoord</label>
                                <input type="password" id="cm-register-password-confirm" required>
                            </div>
                            
                            <div class="cm-form-actions">
                                <button type="submit" class="cm-btn cm-btn-primary">Account aanmaken</button>
                            </div>
                        </form>
                        
                        <div class="cm-form-footer">
                            <button class="cm-btn-link" onclick="location.reload()">← Terug</button>
                        </div>
                    </div>
                    
                    <!-- Loading state -->
                    <div id="cm-loading" class="cm-loading" style="display: none;">
                        <div class="cm-spinner"></div>
                        <p>Even geduld...</p>
                    </div>
                    
                    <!-- Error messages -->
                    <div id="cm-error" class="cm-error" style="display: none;"></div>
                    
                    <!-- Success message -->
                    <div id="cm-success" class="cm-success" style="display: none;"></div>
                </div>
                
                <!-- Info box -->
                <div class="cm-info-box">
                    <h3>Wat gebeurt er na acceptatie?</h3>
                    <ul>
                        <li>Je wordt toegevoegd als trainer aan het team</li>
                        <li>Je krijgt toegang tot het Club Manager dashboard</li>
                        <li>Je kunt spelers bekijken en evalueren</li>
                        <li>Je ontvangt team lidmaatschap voordelen</li>
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
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
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
            background: #f97316;
            color: white;
        }
        
        .cm-btn-primary:hover {
            background: #ea580c;
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
            border-color: #f97316;
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
            color: #f97316;
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
            border-top-color: #f97316;
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
     * Render acceptance form for logged in users
     */
    private function render_logged_in_acceptance($invitation, $token) {
        $team = $invitation->get_team();
        $inviter_id = get_post_meta($invitation->get_id(), '_sender_id', true);
        $inviter = get_user_by('id', $inviter_id);
        $message = get_post_meta($invitation->get_id(), '_cm_message', true);
        
        ob_start();
        ?>
        <div class="cm-invitation-wrapper">
            <div class="cm-invitation-container">
                <div class="cm-invitation-header">
                    <h1>Uitnodiging Accepteren</h1>
                </div>
                
                <div style="padding: 30px;">
                    <p>Je bent uitgenodigd door <strong><?php echo $inviter ? esc_html($inviter->display_name) : 'Someone'; ?></strong> 
                    om trainer te worden bij <strong><?php echo $team ? esc_html($team->get_name()) : 'Unknown Team'; ?></strong>.</p>
                    
                    <?php if (!empty($message)): ?>
                        <div class="cm-invitation-message" style="margin: 20px 0;">
                            <h3>Persoonlijk bericht:</h3>
                            <p><?php echo nl2br(esc_html($message)); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" style="margin-top: 30px;">
                        <?php wp_nonce_field('accept_trainer_invitation', 'invitation_nonce'); ?>
                        <input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
                        
                        <div style="display: flex; gap: 15px;">
                            <button type="submit" name="accept_invitation" value="1" class="cm-btn cm-btn-primary">
                                Accepteren
                            </button>
                            <button type="submit" name="decline_invitation" value="1" class="cm-btn" style="background: #dc2626; color: white;">
                                Weigeren
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
                    <h3>Gelukt!</h3>
                    <p>' . esc_html($result['message']) . '</p>
                    <p style="margin-top: 15px;">
                        <a href="' . home_url('/club-manager/') . '" class="cm-btn cm-btn-primary">
                            Ga naar Club Manager Dashboard
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
            wp_send_json_error('Onjuist wachtwoord. Probeer het opnieuw.');
        }
        
        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        
        // Process invitation
        $result = $this->process_invitation_for_user($user->ID, $token);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'],
                'redirect' => home_url('/club-manager/')
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
            wp_send_json_error('Kon geen account aanmaken. ' . $user_id->get_error_message());
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
                'redirect' => home_url('/club-manager/')
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Process invitation for a specific user
     */
    private function process_invitation_for_user($user_id, $token) {
        if (!function_exists('wc_memberships_for_teams_get_invitation_by_token')) {
            return array(
                'success' => false,
                'message' => 'WooCommerce Teams for Memberships is vereist.'
            );
        }
        
        $invitation = wc_memberships_for_teams_get_invitation_by_token($token);
        
        if (!$invitation || $invitation->get_status() !== 'pending') {
            return array(
                'success' => false,
                'message' => 'Uitnodiging niet gevonden of al geaccepteerd.'
            );
        }
        
        // Accept the WC Teams invitation
        try {
            $invitation->accept($user_id);
            
            // Get team info
            $team = $invitation->get_team();
            $team_name = $team ? $team->get_name() : 'Unknown Team';
            
            // Add to Club Manager trainer table
            $cm_team_id = get_post_meta($invitation->get_id(), '_cm_team_id', true);
            $role = get_post_meta($invitation->get_id(), '_cm_role', true) ?: 'trainer';
            $inviter_id = get_post_meta($invitation->get_id(), '_sender_id', true);
            
            if ($cm_team_id) {
                $this->add_trainer_to_team($cm_team_id, $user_id, $role, $inviter_id);
            }
            
            // Send notification
            if ($inviter_id) {
                $this->send_acceptance_notification($inviter_id, $user_id, array($team_name));
            }
            
            return array(
                'success' => true,
                'message' => 'Je bent succesvol toegevoegd aan het team!'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Er is een fout opgetreden: ' . $e->getMessage()
            );
        }
    }
    
    // ... (rest of the methods remain the same)
    
    /**
     * Process the invitation acceptance
     */
    private function process_invitation_acceptance($invitations, $user_id) {
        global $wpdb;
        
        $trainers_table = Club_Manager_Database::get_table_name('team_trainers');
        $invitations_table = Club_Manager_Database::get_table_name('trainer_invitations');
        
        $success = true;
        $teams_joined = [];
        $wc_teams_joined = [];
        
        // Add trainer to each team
        foreach ($invitations as $invitation) {
            // Add to Club Manager
            if ($this->add_trainer_to_team($invitation->team_id, $user_id, $invitation->role, $invitation->invited_by)) {
                $teams_joined[] = $invitation->team_name;
                
                // Add to WooCommerce team
                $wc_result = $this->add_to_wc_team($invitation->team_id, $user_id);
                if ($wc_result['success']) {
                    $wc_teams_joined[] = $wc_result['team_name'];
                }
            } else {
                $success = false;
            }
        }
        
        // Update invitation status
        if ($success) {
            $wpdb->update(
                $invitations_table,
                [
                    'status' => 'accepted',
                    'accepted_at' => current_time('mysql')
                ],
                ['token' => $invitations[0]->token],
                ['%s', '%s'],
                ['%s']
            );
            
            // Send notification to inviter
            $this->send_acceptance_notification($invitations[0]->invited_by, $user_id, $teams_joined);
            
            $message = '<div class="cm-success">
                <h3>Gelukt!</h3>
                <p>Je bent toegevoegd als trainer aan: ' . implode(', ', $teams_joined) . '</p>
                <p style="margin-top: 15px;">
                    <a href="' . home_url('/club-manager/') . '" class="cm-btn cm-btn-primary">
                        Ga naar Club Manager Dashboard
                    </a>
                </p>
            </div>';
            
            return $message;
        } else {
            return '<div class="cm-error">
                <p>Er is een fout opgetreden bij het accepteren van de uitnodiging. Probeer het opnieuw of neem contact op.</p>
            </div>';
        }
    }
    
    /**
     * Process invitation decline
     */
    private function process_invitation_decline($invitation) {
        try {
            $invitation->delete();
            
            return '<div class="cm-success">
                <h3>Uitnodiging geweigerd</h3>
                <p>Je hebt de uitnodiging om trainer te worden geweigerd.</p>
            </div>';
        } catch (Exception $e) {
            return '<div class="cm-error">
                <p>Er is een fout opgetreden bij het weigeren van de uitnodiging.</p>
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
     * Add user to WooCommerce team
     */
    private function add_to_wc_team($cm_team_id, $user_id) {
        if (!function_exists('wc_memberships_for_teams')) {
            return ['success' => false, 'message' => 'Teams for Memberships not active'];
        }
        
        // Find the WooCommerce team associated with this Club Manager team
        global $wpdb;
        $teams_table = Club_Manager_Database::get_table_name('teams');
        
        // Get the team owner
        $team_owner_id = $wpdb->get_var($wpdb->prepare(
            "SELECT created_by FROM $teams_table WHERE id = %d",
            $cm_team_id
        ));
        
        if (!$team_owner_id) {
            return ['success' => false, 'message' => 'Team owner not found'];
        }
        
        // Find the WooCommerce team
        $wc_team = null;
        
        if (function_exists('wc_memberships_for_teams_get_user_teams')) {
            $owner_teams = wc_memberships_for_teams_get_user_teams($team_owner_id);
            
            if (!empty($owner_teams)) {
                foreach ($owner_teams as $team) {
                    if (is_object($team)) {
                        // Check if owner has owner or manager role
                        if (method_exists($team, 'get_member')) {
                            $member = $team->get_member($team_owner_id);
                            if ($member && method_exists($member, 'get_role')) {
                                $role = $member->get_role();
                                if (in_array($role, array('owner', 'manager'))) {
                                    $wc_team = $team;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (!$wc_team) {
            return ['success' => false, 'message' => 'No WooCommerce team found'];
        }
        
        // Add member to team
        try {
            if (method_exists($wc_team, 'add_member')) {
                $member = $wc_team->add_member($user_id);
                
                if ($member) {
                    return [
                        'success' => true,
                        'team_name' => $wc_team->get_name(),
                        'member_id' => $member->get_id()
                    ];
                }
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Could not add member to team'];
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
        
        $subject = sprintf('[%s] Trainer uitnodiging geaccepteerd', get_bloginfo('name'));
        
        $message = sprintf(
            "Hallo %s,\n\n%s heeft jouw uitnodiging geaccepteerd om trainer te worden voor de volgende teams:\n\n%s\n\nZe hebben nu toegang tot het Club Manager dashboard en kunnen spelers bekijken/evalueren in deze teams.\n\nMet vriendelijke groet,\n%s",
            $inviter->display_name,
            $trainer->display_name,
            implode("\n", array_map(function($team) { return "- " . $team; }, $teams)),
            get_bloginfo('name')
        );
        
        wp_mail($inviter->user_email, $subject, $message);
    }
}