<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MessageTemplate;
use App\Models\Sequence;
use App\Models\SequenceStep;
use Illuminate\Database\Seeder;

/**
 * Seeds the 12-step onboarding sequence for new chatters.
 * Trigger: chatter.registered → auto-enrolled.
 */
class OnboardingSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $sequence = Sequence::updateOrCreate(
            ['name' => 'Onboarding'],
            [
                'description' => '12-step onboarding sequence for new chatters',
                'status' => 'active',
                'trigger_event' => 'chatter.registered',
                'priority' => 90,
                'max_concurrent' => 3,
                'is_repeatable' => false,
                'version' => 1,
                'exit_conditions' => [
                    ['type' => 'days_elapsed', 'value' => 30],
                    ['type' => 'opt_out'],
                ],
            ]
        );

        // Delete existing steps for clean re-seed
        $sequence->steps()->delete();

        // Resolve template slugs to UUIDs
        $templateMap = MessageTemplate::whereIn('slug', [
            'onboarding_welcome', 'onboarding_telegram_invite',
            'first_share_encouragement', 'training_reminder',
            'no_sale_motivation', 'recruitment_encouragement',
        ])->pluck('id', 'slug')->toArray();

        $steps = [
            // Step 0: Immediate welcome message
            [
                'step_order' => 0,
                'type' => 'message',
                'template_id' => $templateMap['onboarding_welcome'] ?? null,
                'delay_seconds' => 0,
                'channel' => null, // auto-resolve
                'metadata' => ['description' => 'Bienvenue — message immédiat après inscription'],
            ],
            // Step 1: 1h delay
            [
                'step_order' => 1,
                'type' => 'delay',
                'delay_seconds' => 3600,
                'metadata' => ['description' => 'Attente 1h avant invitation Telegram'],
            ],
            // Step 2: Telegram invitation
            [
                'step_order' => 2,
                'type' => 'message',
                'template_id' => $templateMap['onboarding_telegram_invite'] ?? null,
                'delay_seconds' => 0,
                'metadata' => ['description' => 'Invitation à connecter Telegram'],
            ],
            // Step 3: Condition — check if Telegram linked
            [
                'step_order' => 3,
                'type' => 'condition',
                'delay_seconds' => 86400, // check after 24h
                'condition_rules' => [
                    'conditions' => [
                        ['field' => 'telegram_id', 'operator' => 'is_not_null'],
                    ],
                    'exit_on_fail' => false,
                    'jump_to_step' => 2, // re-send telegram invite if not linked
                ],
                'metadata' => ['description' => 'Vérifier si Telegram est connecté, sinon relancer'],
            ],
            // Step 4: 24h delay
            [
                'step_order' => 4,
                'type' => 'delay',
                'delay_seconds' => 86400,
                'metadata' => ['description' => 'Attente J+1'],
            ],
            // Step 5: First share encouragement
            [
                'step_order' => 5,
                'type' => 'message',
                'template_id' => $templateMap['first_share_encouragement'] ?? null,
                'delay_seconds' => 0,
                'metadata' => ['description' => 'Encourager le premier partage de lien'],
            ],
            // Step 6: 48h delay
            [
                'step_order' => 6,
                'type' => 'delay',
                'delay_seconds' => 172800,
                'metadata' => ['description' => 'Attente J+3'],
            ],
            // Step 7: Training reminder
            [
                'step_order' => 7,
                'type' => 'message',
                'template_id' => $templateMap['training_reminder'] ?? null,
                'delay_seconds' => 0,
                'metadata' => ['description' => 'Rappel pour compléter la formation'],
            ],
            // Step 8: Condition — check if has first sale
            [
                'step_order' => 8,
                'type' => 'condition',
                'delay_seconds' => 259200, // check at J+6
                'condition_rules' => [
                    'conditions' => [
                        ['field' => 'total_sales', 'operator' => 'gt', 'value' => 0],
                    ],
                    'exit_on_fail' => false,
                ],
                'metadata' => ['description' => 'Vérifier si première vente réalisée'],
            ],
            // Step 9: No-sale motivation (only reached if condition above fails and doesn't jump)
            [
                'step_order' => 9,
                'type' => 'message',
                'template_id' => $templateMap['no_sale_motivation'] ?? null,
                'delay_seconds' => 0,
                'metadata' => ['description' => 'Motivation pour ceux sans vente à J+6'],
            ],
            // Step 10: 72h delay
            [
                'step_order' => 10,
                'type' => 'delay',
                'delay_seconds' => 259200,
                'metadata' => ['description' => 'Attente J+9'],
            ],
            // Step 11: Recruitment encouragement + recap
            [
                'step_order' => 11,
                'type' => 'message',
                'template_id' => $templateMap['recruitment_encouragement'] ?? null,
                'delay_seconds' => 0,
                'metadata' => ['description' => 'Encourager le recrutement + récap de progression'],
            ],
        ];

        foreach ($steps as $step) {
            SequenceStep::create(array_merge($step, [
                'sequence_id' => $sequence->id,
            ]));
        }

        $this->command->info("Onboarding sequence seeded with 12 steps.");
    }
}
