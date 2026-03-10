<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Sequence;
use App\Models\SequenceStep;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SequenceBuilderPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static string $view = 'filament.pages.sequence-builder';
    protected static ?string $title = 'Sequence Builder';
    protected static ?string $navigationGroup = 'Engagement';

    public ?string $selectedSequenceId = null;
    public array $steps = [];
    public ?string $sequenceName = null;
    public ?string $triggerEvent = null;
    public int $priority = 50;
    public int $version = 1;

    public function mount(): void
    {
        $this->steps = [];
    }

    public function loadSequence(): void
    {
        if (!$this->selectedSequenceId) {
            return;
        }

        $sequence = Sequence::with('steps')->find($this->selectedSequenceId);
        if (!$sequence) {
            return;
        }

        $this->sequenceName = $sequence->name;
        $this->triggerEvent = $sequence->trigger_event;
        $this->priority = $sequence->priority ?? 50;
        $this->version = $sequence->version ?? 1;

        $this->steps = $sequence->steps->sortBy('step_order')->map(fn (SequenceStep $step) => [
            'step_order' => $step->step_order,
            'type' => $step->type,
            'template_id' => $step->template_id,
            'delay_seconds' => $step->delay_seconds,
            'channel' => $step->channel,
            'condition_rules' => json_encode($step->condition_rules ?? []),
            'metadata' => json_encode($step->metadata ?? []),
        ])->values()->toArray();
    }

    public function saveSequence(): void
    {
        if (!$this->selectedSequenceId) {
            Notification::make()->title('Select a sequence first')->warning()->send();
            return;
        }

        $sequence = Sequence::find($this->selectedSequenceId);
        if (!$sequence) {
            return;
        }

        // Snapshot current version before edit
        $sequence->update([
            'snapshot_before_edit' => $sequence->steps->toArray(),
            'version' => $this->version + 1,
            'priority' => $this->priority,
            'trigger_event' => $this->triggerEvent,
        ]);

        // Delete existing steps and recreate
        $sequence->steps()->delete();

        foreach ($this->steps as $i => $stepData) {
            SequenceStep::create([
                'sequence_id' => $sequence->id,
                'step_order' => $i,
                'type' => $stepData['type'] ?? 'message',
                'template_id' => $stepData['template_id'] ?? null,
                'delay_seconds' => (int) ($stepData['delay_seconds'] ?? 0),
                'channel' => $stepData['channel'] ?? null,
                'condition_rules' => json_decode($stepData['condition_rules'] ?? '{}', true),
                'metadata' => json_decode($stepData['metadata'] ?? '{}', true),
            ]);
        }

        $this->version++;

        Notification::make()
            ->title("Sequence saved (v{$this->version})")
            ->success()
            ->send();
    }

    public function addStep(): void
    {
        $this->steps[] = [
            'step_order' => count($this->steps),
            'type' => 'message',
            'template_id' => null,
            'delay_seconds' => 0,
            'channel' => null,
            'condition_rules' => '{}',
            'metadata' => '{}',
        ];
    }

    public function removeStep(int $index): void
    {
        unset($this->steps[$index]);
        $this->steps = array_values($this->steps);
    }

    public function moveStepUp(int $index): void
    {
        if ($index <= 0) {
            return;
        }
        [$this->steps[$index - 1], $this->steps[$index]] = [$this->steps[$index], $this->steps[$index - 1]];
    }

    public function moveStepDown(int $index): void
    {
        if ($index >= count($this->steps) - 1) {
            return;
        }
        [$this->steps[$index + 1], $this->steps[$index]] = [$this->steps[$index], $this->steps[$index + 1]];
    }

    public function getSequenceOptions(): array
    {
        return Sequence::orderBy('priority', 'desc')->pluck('name', 'id')->toArray();
    }

    public static function getStepTypes(): array
    {
        return [
            'message' => 'Message',
            'delay' => 'Delay',
            'condition' => 'Condition',
            'ab_split' => 'A/B Split',
            'action' => 'Action',
            'webhook' => 'Webhook',
        ];
    }
}
