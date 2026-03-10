<?php

return [
    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'chatters' => 'Chatters',
        'sequences' => 'Sequences',
        'campaigns' => 'Campaigns',
        'missions' => 'Missions',
        'badges' => 'Badges',
        'segments' => 'Segments',
        'templates' => 'Message Templates',
        'message_logs' => 'Message History',
        'ab_tests' => 'A/B Tests',
        'leaderboard' => 'Leaderboard',
        'leagues' => 'Leagues',
        'whatsapp_numbers' => 'WhatsApp Numbers',
        'telegram_bots' => 'Telegram Bots',
        'suppression_list' => 'Suppression List',
        'fraud_flags' => 'Fraud Alerts',
        'admin_alerts' => 'Admin Alerts',
        'admin_users' => 'Admin Users',
        'scheduled_reports' => 'Scheduled Reports',
        'consent_records' => 'GDPR Consent',
        'settings' => 'Settings',
    ],

    // Groups
    'groups' => [
        'engagement' => 'Engagement',
        'gamification' => 'Gamification',
        'messaging' => 'Messaging',
        'analytics' => 'Analytics',
        'infrastructure' => 'Infrastructure',
        'compliance' => 'Compliance',
        'administration' => 'Administration',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'Dashboard',
        'active_chatters' => 'Active Chatters',
        'messages_today' => 'Messages Today',
        'messages_cost' => 'Messages Cost (month)',
        'active_sequences' => 'Active Sequences',
        'conversion_rate' => 'Conversion Rate',
        'avg_streak' => 'Average Streak',
        'revenue_attributed' => 'Revenue Attributed',
        'churn_rate' => 'Churn Rate',
    ],

    // Chatters
    'chatter' => [
        'display_name' => 'Name',
        'firebase_uid' => 'Firebase UID',
        'language' => 'Language',
        'country' => 'Country',
        'status' => 'Status',
        'lifecycle_state' => 'Lifecycle State',
        'preferred_channel' => 'Preferred Channel',
        'level' => 'Level',
        'total_xp' => 'Total XP',
        'total_sales' => 'Total Sales',
        'total_earned_cents' => 'Total Earned',
        'current_streak' => 'Current Streak',
        'longest_streak' => 'Longest Streak',
        'telegram_linked' => 'Telegram Linked',
        'whatsapp_opted_in' => 'WhatsApp Active',
        'registered_at' => 'Registered',
        'last_active_at' => 'Last Active',
    ],

    // Sequences
    'sequence' => [
        'name' => 'Name',
        'trigger_event' => 'Trigger Event',
        'priority' => 'Priority',
        'status' => 'Status',
        'steps_count' => 'Steps',
        'enrolled' => 'Enrolled',
        'completed' => 'Completed',
    ],

    // Campaigns
    'campaign' => [
        'name' => 'Name',
        'status' => 'Status',
        'scheduled_at' => 'Scheduled',
        'sent' => 'Sent',
        'delivered' => 'Delivered',
        'clicked' => 'Clicked',
        'total_cost' => 'Total Cost',
    ],

    // Missions
    'mission' => [
        'name' => 'Name',
        'type' => 'Type',
        'target_value' => 'Target',
        'xp_reward' => 'XP Reward',
        'is_active' => 'Active',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'secret' => 'Secret',
    ],

    // Common
    'common' => [
        'created_at' => 'Created',
        'updated_at' => 'Updated',
        'actions' => 'Actions',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'view' => 'View',
        'create' => 'Create',
        'export' => 'Export',
        'import' => 'Import',
        'filter' => 'Filter',
        'search' => 'Search',
        'yes' => 'Yes',
        'no' => 'No',
        'all' => 'All',
        'none' => 'None',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'draft' => 'Draft',
        'paused' => 'Paused',
    ],

    // WhatsApp
    'whatsapp' => [
        'number' => 'Number',
        'label' => 'Label',
        'health_score' => 'Health Score',
        'circuit_state' => 'Circuit State',
        'daily_limit' => 'Daily Limit',
        'sent_today' => 'Sent Today',
        'country' => 'Country',
    ],

    // Telegram
    'telegram' => [
        'bot_username' => 'Bot Username',
        'bot_label' => 'Label',
        'assigned_chatters' => 'Assigned Chatters',
        'total_sent' => 'Total Sent',
        'is_active' => 'Active',
    ],

    // Fraud
    'fraud' => [
        'flag_type' => 'Type',
        'severity' => 'Severity',
        'evidence' => 'Evidence',
        'resolved' => 'Resolved',
        'resolved_by' => 'Resolved By',
    ],
];
