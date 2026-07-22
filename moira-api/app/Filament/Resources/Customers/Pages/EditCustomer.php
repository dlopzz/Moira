<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
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
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }
}
