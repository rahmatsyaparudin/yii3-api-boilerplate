<?php

declare(strict_types=1);

/**
 * Application messages (English)
 */
return [
    // General Success
    'success' => 'Success',
    'ok' => 'OK',
    'completed' => '{resource} completed successfully',
    'processed' => '{resource} processed successfully',
    
    // Global CRUD Operations
    'resource.created' => '{resource} has been created successfully.',
    'resource.updated' => '{resource} has been updated successfully.',
    'resource.deleted' => '{resource} has been deleted successfully.',
    'resource.restored' => '{resource} has been restored successfully.',
    'resource.list_retrieved' => '{resource} list retrieved successfully.',
    'resource.details_retrieved' => '{resource} details retrieved successfully.',
    'resource.no_changes_detected' => 'No changes detected for {resource}. The submitted data is identical to the current record.',
    
    // Specific Operations (non-CRUD)
    'user.activated' => 'User {name} has been activated successfully',
    'user.deactivated' => 'User {name} has been deactivated successfully',
    'user.password_changed' => 'Password has been changed successfully',
    'user.profile_updated' => 'Profile has been updated successfully',
    
    // Authentication
    'auth.login_success' => 'Login successful',
    'auth.logout_success' => 'Logout successful',
    'auth.password_reset_sent' => 'Password reset link has been sent to your email',
    'auth.password_reset_success' => 'Your password has been reset successfully',
    'auth.email_verified' => 'Email address has been verified successfully',
    'auth.account_created' => 'Your account has been created successfully',
    
    // Data Operations
    'data.imported' => 'Data has been imported successfully',
    'data.exported' => 'Data has been exported successfully',
    'data.synced' => 'Data has been synchronized successfully',
    'data.backed_up' => 'Data has been backed up successfully',
    'data.restored' => 'Data has been restored successfully',
    'data.cleared' => 'Data has been cleared successfully',
    
    // File Operations
    'file.uploaded' => 'File has been uploaded successfully',
    'file.downloaded' => 'File has been downloaded successfully',
    'file.deleted' => 'File has been deleted successfully',
    'file.moved' => 'File has been moved successfully',
    'file.copied' => 'File has been copied successfully',
    
    // Settings & Configuration
    'settings.updated' => 'Settings have been updated successfully',
    'settings.saved' => 'Settings have been saved successfully',
    'settings.reset' => 'Settings have been reset to default successfully',
    'configuration.updated' => 'Configuration has been updated successfully',
    
    // System Operations
    'system.cache_cleared' => 'Cache has been cleared successfully',
    'system.maintenance_enabled' => 'Maintenance mode has been enabled',
    'system.maintenance_disabled' => 'Maintenance mode has been disabled',
    'system.restarted' => 'System has been restarted successfully',
    
    // Notifications
    'notification.sent' => 'Notification has been sent successfully',
    'notification.marked_read' => 'Notification marked as read',
    'notification.marked_unread' => 'Notification marked as unread',
    'notification.deleted' => 'Notification has been deleted successfully',
    
    // Reports & Analytics
    'report.generated' => 'Report has been generated successfully',
    'report.downloaded' => 'Report has been downloaded successfully',
    'analytics.data_refreshed' => 'Analytics data has been refreshed',
    
    // Bulk Operations
    'bulk.created' => '{resource} have been created successfully',
    'bulk.updated' => '{resource} have been updated successfully',
    'bulk.deleted' => '{resource} have been deleted successfully',
    'bulk.imported' => '{resource} have been imported successfully',
    'bulk.exported' => '{resource} have been exported successfully',
    
    // API Operations
    'api.request_processed' => 'API request has been processed successfully',
    'api.rate_limit_reset' => 'API rate limit has been reset successfully',
    'api.key_generated' => 'API key has been generated successfully',
    'api.key_revoked' => 'API key has been revoked successfully',
];
