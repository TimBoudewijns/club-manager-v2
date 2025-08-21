<?php

/**
 * Handle sync-related AJAX requests
 */
class Club_Manager_Sync_Ajax extends Club_Manager_Ajax_Handler {
    
    /**
     * Initialize AJAX actions
     */
    public function init() {
        add_action('wp_ajax_cm_sync_invitations', array($this, 'sync_invitations'));
    }
    
    /**
     * Sync existing invitations
     */
    public function sync_invitations() {
        $user_id = $this->verify_request();
        
        // Check if user can manage teams
        if (!Club_Manager_User_Permissions_Helper::is_club_owner_or_manager($user_id)) {
            wp_send_json_error('Je hebt geen rechten om uitnodigingen te synchroniseren');
            return;
        }
        
        // Include the sync class
        if (!class_exists('Club_Manager_Invitation_Sync')) {
            require_once CLUB_MANAGER_PLUGIN_DIR . 'includes/admin/class-invitation-sync.php';
        }
        
        // Run the sync
        $results = Club_Manager_Invitation_Sync::sync_existing_invitations();
        
        // Update last sync time
        update_option('cm_invitation_sync_last_run', time());
        
        // Return results
        wp_send_json_success(array(
            'message' => sprintf(
                '%d uitnodigingen verwerkt, %d bijgewerkt',
                $results['processed'],
                $results['updated']
            ),
            'processed' => $results['processed'],
            'updated' => $results['updated'],
            'errors' => $results['errors']
        ));
    }
}