<?php

return [
    'users' => [
        'created' => 'New user successfully created.',
        'updated' => 'User data successfully updated.',
        'deleted' => 'User successfully deleted.',
        'bulk_updated' => ':count user(s) successfully :status.',
        'activated' => 'activated',
        'deactivated' => 'deactivated',
    ],
    'roles' => [
        'created' => 'Role :name successfully added.',
        'renamed' => 'Role :old renamed to :new.',
        'deleted' => 'Role :name successfully deleted.',
        'permissions_updated' => 'Permissions for role :name successfully updated.',
    ],
    'features' => [
        'enabled' => 'Feature :label successfully enabled.',
        'disabled' => 'Feature :label successfully disabled.',
        'activity_enabled' => 'Feature :label enabled.',
        'activity_disabled' => 'Feature :label disabled.',
        'flags' => [
            'passkeys_login' => [
                'label' => 'Passkey Sign-in',
                'description' => 'Passkey sign-in and management. When disabled, passkey routes return 404 and the panel is hidden from Settings.',
            ],
            'magic_link_login' => [
                'label' => 'Email Magic Link Sign-in',
                'description' => 'Sign-in via link sent by email. When disabled, all magic link routes return 404.',
            ],
        ],
    ],
    'impersonation' => [
        'already_impersonating' => 'You are already in impersonation mode.',
        'started' => 'You are now signed in as :name.',
        'activity_started' => ':actor signed in as :target.',
        'not_impersonating' => 'You are not in impersonation mode.',
        'origin_unavailable' => 'Original account is no longer available. Please sign in again.',
        'stopped' => 'You are back signed in as :name.',
        'activity_stopped' => ':actor returned from :target.',
    ],
    'security' => [
        'two_factor_initiated' => 'Two-factor authentication initiated. Scan the QR code and enter the verification code.',
        'two_factor_invalid_code' => 'Invalid verification code. Please check your authenticator app.',
        'two_factor_enabled' => 'Two-factor authentication successfully enabled.',
        'two_factor_disabled' => 'Two-factor authentication disabled.',
        'recovery_codes_regenerated' => 'New recovery codes successfully generated.',
        'password_mismatch' => 'Password does not match.',
    ],
    'settings' => [
        'account_updated' => 'Account data successfully updated.',
        'password_updated' => 'Password successfully changed.',
        'other_sessions_logged_out' => 'All other sessions successfully logged out.',
        'current_session_cannot_be_terminated' => 'Current active session cannot be logged out from here.',
        'session_logged_out' => 'Device session successfully logged out.',
        'system_updated' => 'System configuration successfully saved.',
        'activity_system_updated' => 'System configuration updated.',
    ],
    'policies' => [
        'user' => [
            'update_superadmin_denied' => 'Superadmin account cannot be modified.',
            'change_own_role_denied' => 'You cannot change the role of your own account.',
            'deactivate_self_denied' => 'You cannot deactivate your own account.',
            'deactivate_superadmin_denied' => 'Superadmin account cannot be deactivated.',
            'delete_self_denied' => 'You cannot delete your own account.',
            'delete_superadmin_denied' => 'Superadmin account cannot be deleted.',
            'impersonate_superadmin_only' => 'Only superadmin can impersonate other users.',
            'impersonate_self_denied' => 'You cannot impersonate your own account.',
            'impersonate_other_superadmin_denied' => 'Other superadmin accounts cannot be impersonated.',
            'impersonate_inactive_denied' => 'Inactive accounts cannot be impersonated.',
        ],
        'role' => [
            'rename_superadmin_denied' => 'Superadmin role name cannot be changed.',
            'delete_superadmin_denied' => 'Superadmin role cannot be deleted.',
            'delete_in_use_denied' => 'Role :name is still used by :count user(s) and cannot be deleted.',
            'change_superadmin_permissions_denied' => 'Superadmin permissions cannot be changed.',
        ],
    ],
    'system' => [
        'maintenance_default_message' => 'The application is currently under maintenance. Please try again later.',
    ],
];
