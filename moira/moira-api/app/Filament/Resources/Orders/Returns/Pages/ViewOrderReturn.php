<?php

namespace App\Filament\Resources\Orders\Returns\Pages;

use App\Filament\Resources\Orders\Returns\OrderReturnResource;
use App\Models\OrderReturn;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderReturn extends ViewRecord
{
    protected static string $resource = OrderReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Aprobar')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn () => $this->record->status === OrderReturn::STATUS_REQUESTED)
                ->requiresConfirmation()
                ->action(fn () => $this->transition(OrderReturn::STATUS_APPROVED)),

            Action::make('reject')
                ->label('Rechazar')
                ->color('danger')
                ->icon('heroicon-o-x-mark')
                ->visible(fn () => in_array($this->record->status, [OrderReturn::STATUS_REQUESTED, OrderReturn::STATUS_APPROVED], true))
                ->requiresConfirmation()
                ->action(fn () => $this->transition(OrderReturn::STATUS_REJECTED)),

            Action::make('markReceived')
                ->label('Marcar como recibida')
                ->icon('heroicon-o-inbox-arrow-down')
                ->visible(fn () => $this->record->status === OrderReturn::STATUS_APPROVED)
                ->requiresConfirmation()
                ->action(fn () => $this->transition(OrderReturn::STATUS_RECEIVED)),

            Action::make('markRefunded')
                ->label('Marcar como reembolsada')
                ->icon('heroicon-o-banknotes')
                ->visible(fn () => $this->record->status === OrderReturn::STATUS_RECEIVED)
                ->requiresConfirmation()
                ->action(fn () => $this->transition(OrderReturn::STATUS_REFUNDED)),

            Action::make('close')
                ->label('Cerrar sin reembolso')
                ->color('gray')
                ->visible(fn () => $this->record->status === OrderReturn::STATUS_RECEIVED)
                ->requiresConfirmation()
                ->action(fn () => $this->transition(OrderReturn::STATUS_CLOSED)),
        ];
    }

    private function transition(string $status): void
    {
        $this->record->transitionStatus($status);

        Notification::make()
            ->title('Estado actualizado')
            ->success()
            ->send();
    }
}
