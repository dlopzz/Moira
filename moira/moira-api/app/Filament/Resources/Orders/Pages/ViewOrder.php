<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Returns\OrderReturnResource;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeStatus')
                ->label('Cambiar estado')
                ->icon('heroicon-o-arrow-path')
                ->schema([
                    Select::make('status')
                        ->label('Nuevo estado')
                        ->options(Order::STATUS_LABELS)
                        ->default(fn () => $this->record->status)
                        ->required(),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $this->record->transitionStatus($data['status']);

                    Notification::make()
                        ->title('Estado actualizado')
                        ->success()
                        ->send();
                }),

            Action::make('generateLabel')
                ->label('Generar etiqueta')
                ->icon('heroicon-o-truck')
                ->visible(fn () => ! in_array($this->record->status, [...Order::RESTOCKING_STATUSES, 'delivered'], true))
                ->schema([
                    TextInput::make('tracking_number')
                        ->label('N° de seguimiento (opcional)')
                        ->default(fn () => $this->record->tracking_number),
                ])
                ->action(function (array $data): StreamedResponse {
                    if (filled($data['tracking_number'] ?? null)) {
                        $this->record->tracking_number = $data['tracking_number'];
                        $this->record->save();
                    }
                    $this->record->transitionStatus('shipped');

                    return response()->streamDownload(
                        fn () => print (Pdf::loadView('pdf.order-label', ['order' => $this->record])->output()),
                        "etiqueta-orden-{$this->record->id}.pdf"
                    );
                }),

            Action::make('generateInvoice')
                ->label('Generar comprobante')
                ->icon('heroicon-o-document-text')
                ->action(fn (): StreamedResponse => response()->streamDownload(
                    fn () => print (Pdf::loadView('pdf.order-invoice', [
                        'order' => $this->record->load(['items', 'customer', 'paymentMethod']),
                    ])->output()),
                    "comprobante-orden-{$this->record->id}.pdf"
                )),

            Action::make('createReturn')
                ->label('Crear devolución')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn () => ! in_array($this->record->status, Order::RESTOCKING_STATUSES, true))
                ->url(fn () => OrderReturnResource::getUrl('create', ['order_id' => $this->record->id])),
        ];
    }
}
