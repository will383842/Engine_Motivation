<?php

return [
    // Navigation
    'nav' => [
        'dashboard' => 'Tableau de bord',
        'chatters' => 'Chatters',
        'sequences' => 'Séquences',
        'campaigns' => 'Campagnes',
        'missions' => 'Missions',
        'badges' => 'Badges',
        'segments' => 'Segments',
        'templates' => 'Templates de messages',
        'message_logs' => 'Historique des messages',
        'ab_tests' => 'Tests A/B',
        'leaderboard' => 'Classement',
        'leagues' => 'Ligues',
        'whatsapp_numbers' => 'Numéros WhatsApp',
        'telegram_bots' => 'Bots Telegram',
        'suppression_list' => 'Liste de suppression',
        'fraud_flags' => 'Alertes fraude',
        'admin_alerts' => 'Alertes admin',
        'admin_users' => 'Utilisateurs admin',
        'scheduled_reports' => 'Rapports programmés',
        'consent_records' => 'Consentements RGPD',
        'settings' => 'Paramètres',
    ],

    // Groups
    'groups' => [
        'engagement' => 'Engagement',
        'gamification' => 'Gamification',
        'messaging' => 'Messagerie',
        'analytics' => 'Analytique',
        'infrastructure' => 'Infrastructure',
        'compliance' => 'Conformité',
        'administration' => 'Administration',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'Tableau de bord',
        'active_chatters' => 'Chatters actifs',
        'messages_today' => 'Messages aujourd\'hui',
        'messages_cost' => 'Coût messages (mois)',
        'active_sequences' => 'Séquences actives',
        'conversion_rate' => 'Taux de conversion',
        'avg_streak' => 'Streak moyen',
        'revenue_attributed' => 'Revenu attribué',
        'churn_rate' => 'Taux d\'attrition',
    ],

    // Chatters
    'chatter' => [
        'display_name' => 'Nom',
        'firebase_uid' => 'Firebase UID',
        'language' => 'Langue',
        'country' => 'Pays',
        'status' => 'Statut',
        'lifecycle_state' => 'État lifecycle',
        'preferred_channel' => 'Canal préféré',
        'level' => 'Niveau',
        'total_xp' => 'XP total',
        'total_sales' => 'Ventes totales',
        'total_earned_cents' => 'Gains totaux',
        'current_streak' => 'Streak actuel',
        'longest_streak' => 'Meilleur streak',
        'telegram_linked' => 'Telegram lié',
        'whatsapp_opted_in' => 'WhatsApp actif',
        'registered_at' => 'Inscrit le',
        'last_active_at' => 'Dernière activité',
    ],

    // Sequences
    'sequence' => [
        'name' => 'Nom',
        'trigger_event' => 'Événement déclencheur',
        'priority' => 'Priorité',
        'status' => 'Statut',
        'steps_count' => 'Étapes',
        'enrolled' => 'Inscrits',
        'completed' => 'Terminés',
    ],

    // Campaigns
    'campaign' => [
        'name' => 'Nom',
        'status' => 'Statut',
        'scheduled_at' => 'Programmée le',
        'sent' => 'Envoyés',
        'delivered' => 'Délivrés',
        'clicked' => 'Cliqués',
        'total_cost' => 'Coût total',
    ],

    // Missions
    'mission' => [
        'name' => 'Nom',
        'type' => 'Type',
        'target_value' => 'Objectif',
        'xp_reward' => 'Récompense XP',
        'is_active' => 'Active',
        'daily' => 'Quotidienne',
        'weekly' => 'Hebdomadaire',
        'monthly' => 'Mensuelle',
        'secret' => 'Secrète',
    ],

    // Common
    'common' => [
        'created_at' => 'Créé le',
        'updated_at' => 'Modifié le',
        'actions' => 'Actions',
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
        'delete' => 'Supprimer',
        'edit' => 'Modifier',
        'view' => 'Voir',
        'create' => 'Créer',
        'export' => 'Exporter',
        'import' => 'Importer',
        'filter' => 'Filtrer',
        'search' => 'Rechercher',
        'yes' => 'Oui',
        'no' => 'Non',
        'all' => 'Tous',
        'none' => 'Aucun',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'draft' => 'Brouillon',
        'paused' => 'En pause',
    ],

    // WhatsApp
    'whatsapp' => [
        'number' => 'Numéro',
        'label' => 'Libellé',
        'health_score' => 'Score santé',
        'circuit_state' => 'État circuit',
        'daily_limit' => 'Limite journalière',
        'sent_today' => 'Envoyés aujourd\'hui',
        'country' => 'Pays',
    ],

    // Telegram
    'telegram' => [
        'bot_username' => 'Username bot',
        'bot_label' => 'Libellé',
        'assigned_chatters' => 'Chatters assignés',
        'total_sent' => 'Total envoyés',
        'is_active' => 'Actif',
    ],

    // Fraud
    'fraud' => [
        'flag_type' => 'Type',
        'severity' => 'Sévérité',
        'evidence' => 'Preuves',
        'resolved' => 'Résolu',
        'resolved_by' => 'Résolu par',
    ],
];
