<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Password;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendPasswordReset')
                ->label('Enviar reseteo de contraseña')
                ->button()
                ->icon(Heroicon::Key)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Enviar mail de reseteo de contraseña')
                ->modalDescription(fn (): string => "Se enviará un enlace a {$this->record->email} para que el cliente defina una nueva contraseña.")
                ->action(function (): void {
                    $status = Password::broker('customers')->sendResetLink(['email' => $this->record->email]);

                    if ($status === Password::RESET_LINK_SENT) {
                        Notification::make()
                            ->success()
                            ->title('Mail enviado')
                            ->body("Se envió el enlace de reseteo a {$this->record->email}.")
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->warning()
                        ->title('No se pudo enviar el mail')
                        ->body(__($status))
                        ->send();
                }),
            // Suspender en vez de eliminar: borrar un cliente deja su email
            // bloqueado por el índice único de la tabla, así que ni el admin ni
            // el propio cliente pueden volver a crear la cuenta con ese mail.
            Action::make('suspender')
                ->label('Suspender cliente')
                ->button()
                ->icon(Heroicon::NoSymbol)
                ->color('danger')
                ->visible(fn (): bool => $this->record->is_active && ! $this->record->trashed())
                ->requiresConfirmation()
                ->modalHeading('Suspender cliente')
                ->modalDescription('No va a poder iniciar sesión ni comprar. Se cierran sus sesiones abiertas. Sus datos y órdenes se conservan y podés reactivarlo cuando quieras.')
                ->action(function (): void {
                    $this->record->suspend();

                    Notification::make()
                        ->success()
                        ->title('Cliente suspendido')
                        ->send();
                }),

            Action::make('reactivar')
                ->label('Reactivar cliente')
                ->button()
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->visible(fn (): bool => ! $this->record->is_active && ! $this->record->trashed())
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['is_active' => true]);

                    Notification::make()
                        ->success()
                        ->title('Cliente reactivado')
                        ->send();
                }),

            // Se conserva para rescatar los clientes borrados antes de que se
            // quitara la opción de eliminar.
            RestoreAction::make(),
        ];
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }
}
