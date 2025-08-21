<?php

/**
 * Synchronize existing WC Teams invitations with Club Manager
 */
class Club_Manager_Invitation_Sync {
    
    /**
     * Run the synchronization
     */
    public static function sync_existing_invitations() {
        global $wpdb;
        
        $results = array(
            'processed' => 0,
            'updated' => 0,
            'errors' => array()
        );
        
        // Get ALL pending invitations, not just those with post_parent set
        $all_invitations = $wpdb->get_results(
            "SELECT * FROM {$wpdb->posts} 
            WHERE post_type = 'wc_team_invitation' 
            AND post_status = 'wcmti-pending'
            ORDER BY post_date DESC"
        );
        
        foreach ($all_invitations as $invitation_post) {
            $results['processed']++;
            
            try {
                // Get the invitation metadata
                $email = get_post_meta($invitation_post->ID, '_email', true);
                if (empty($email)) {
                    $email = get_post_meta($invitation_post->ID, '_recipient_email', true);
                }
                
                // If still no email, try the post title
                if (empty($email) && filter_var($invitation_post->post_title, FILTER_VALIDATE_EMAIL)) {
                    $email = $invitation_post->post_title;
                }
                
                if (empty($email)) {
                    $results['errors'][] = "No email found for invitation ID: " . $invitation_post->ID;
                    continue;
                }
                
                // Get or determine the WC team ID
                $wc_team_id = $invitation_post->post_parent;
                
                // If no parent team set, try to find it from metadata
                if (empty($wc_team_id)) {
                    $wc_team_id = get_post_meta($invitation_post->ID, '_team_id', true);
                }
                
                // If still no team, try to find the team from the sender
                if (empty($wc_team_id)) {
                    $sender_id = get_post_meta($invitation_post->ID, '_sender_id', true);
                    if (!empty($sender_id)) {
                        $wc_team_id = self::find_team_for_sender($sender_id);
                    }
                }
                
                // Update the invitation with proper parent if found
                if (!empty($wc_team_id) && $invitation_post->post_parent != $wc_team_id) {
                    $wpdb->update(
                        $wpdb->posts,
                        array('post_parent' => $wc_team_id),
                        array('ID' => $invitation_post->ID),
                        array('%d'),
                        array('%d')
                    );
                    $results['updated']++;
                }
                
                // Also ensure proper metadata is set
                self::ensure_invitation_metadata($invitation_post->ID, $email, $wc_team_id);
                
            } catch (Exception $e) {
                $results['errors'][] = "Error processing invitation ID " . $invitation_post->ID . ": " . $e->getMessage();
            }
        }
        
        return $results;
    }
    
    /**
     * Find the WC team for a sender/owner
     */
    private static function find_team_for_sender($sender_id) {
        if (!function_exists('wc_memberships_for_teams')) {
            return null;
        }
        
        // Get teams where this user is owner/manager
        $managed_teams = Club_Manager_Teams_Helper::get_user_managed_teams($sender_id);
        
        if (!empty($managed_teams)) {
            // Return the first team (usually clubs have one main team)
            return $managed_teams[0]['team_id'];
        }
        
        return null;
    }
    
    /**
     * Ensure invitation has all required metadata
     */
    private static function ensure_invitation_metadata($invitation_id, $email, $wc_team_id = null) {
        // Ensure email is set
        if (!get_post_meta($invitation_id, '_email', true)) {
            update_post_meta($invitation_id, '_email', $email);
        }
        
        if (!get_post_meta($invitation_id, '_recipient_email', true)) {
            update_post_meta($invitation_id, '_recipient_email', $email);
        }
        
        // Ensure team ID is set if available
        if ($wc_team_id && !get_post_meta($invitation_id, '_team_id', true)) {
            update_post_meta($invitation_id, '_team_id', $wc_team_id);
        }
        
        // Set a flag that this invitation has been synced
        update_post_meta($invitation_id, '_cm_synced', true);
        update_post_meta($invitation_id, '_cm_sync_date', current_time('mysql'));
    }
    
    /**
     * Check if sync is needed
     */
    public static function needs_sync() {
        global $wpdb;
        
        // Check if there are any invitations without the sync flag
        $unsynced_count = $wpdb->get_var(
            "SELECT COUNT(p.ID) 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_cm_synced'
            WHERE p.post_type = 'wc_team_invitation' 
            AND p.post_status = 'wcmti-pending'
            AND pm.meta_value IS NULL"
        );
        
        return $unsynced_count > 0;
    }
    
    /**
     * Run sync on admin init if needed
     */
    public static function maybe_run_sync() {
        // Check if sync has been run recently
        $last_sync = get_option('cm_invitation_sync_last_run', 0);
        $time_since_sync = time() - $last_sync;
        
        // Run sync if it hasn't been run in the last hour and there are unsynced invitations
        if ($time_since_sync > 3600 && self::needs_sync()) {
            $results = self::sync_existing_invitations();
            
            // Log results
            if (!empty($results['errors'])) {
                error_log('Club Manager Invitation Sync Errors: ' . print_r($results['errors'], true));
            }
            
            error_log('Club Manager Invitation Sync: Processed ' . $results['processed'] . ', Updated ' . $results['updated']);
            
            // Update last run time
            update_option('cm_invitation_sync_last_run', time());
            
            return $results;
        }
        
        return false;
    }
    
    /**
     * Add admin notice if sync is needed
     */
    public static function admin_notice_sync_needed() {
        if (self::needs_sync()) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>Club Manager:</strong> 
                    Er zijn bestaande trainer uitnodigingen gevonden die gesynchroniseerd moeten worden.
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=club-manager&action=sync-invitations'), 'cm_sync_invitations'); ?>" class="button button-small">
                        Synchroniseer Nu
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Handle manual sync request
     */
    public static function handle_manual_sync() {
        if (isset($_GET['page']) && $_GET['page'] === 'club-manager' 
            && isset($_GET['action']) && $_GET['action'] === 'sync-invitations'
            && wp_verify_nonce($_GET['_wpnonce'], 'cm_sync_invitations')) {
            
            $results = self::sync_existing_invitations();
            update_option('cm_invitation_sync_last_run', time());
            
            // Add admin notice with results
            add_action('admin_notices', function() use ($results) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <strong>Synchronisatie voltooid:</strong> 
                        <?php echo $results['processed']; ?> uitnodigingen verwerkt, 
                        <?php echo $results['updated']; ?> bijgewerkt.
                        <?php if (!empty($results['errors'])): ?>
                            <br><small><?php echo count($results['errors']); ?> fouten opgetreden (zie logs voor details).</small>
                        <?php endif; ?>
                    </p>
                </div>
                <?php
            });
            
            // Redirect to remove action from URL
            wp_redirect(admin_url('admin.php?page=club-manager'));
            exit;
        }
    }
}

// Hook into admin init to run sync if needed
add_action('admin_init', array('Club_Manager_Invitation_Sync', 'maybe_run_sync'));
add_action('admin_init', array('Club_Manager_Invitation_Sync', 'handle_manual_sync'));
add_action('admin_notices', array('Club_Manager_Invitation_Sync', 'admin_notice_sync_needed'));