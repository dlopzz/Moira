<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'role',
    ];

    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}") ?: $this->username);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role?->canAccessPanel() ?? false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    /** super_admin y admin pueden gestionar el panel completo */
    public function isAdmin(): bool
    {
        return in_array($this->role, [Role::SuperAdmin, Role::Admin]);
    }

    /** super_admin, admin y editor pueden gestionar el catálogo */
    public function isEditor(): bool
    {
        return in_array($this->role, [Role::SuperAdmin, Role::Admin, Role::Editor]);
    }

    /** Busca por username o email, el mismo criterio usado para iniciar sesión en el panel */
    public static function findByLogin(string $value): ?self
    {
        return self::where('username', $value)->orWhere('email', $value)->first();
    }
}
