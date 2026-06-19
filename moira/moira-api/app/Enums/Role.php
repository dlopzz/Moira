<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin      = 'admin';
    case Editor     = 'editor';
    case Customer   = 'customer';

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin      => 'Administrador',
            self::Editor     => 'Editor',
            self::Customer   => 'Cliente',
        };
    }

    public function canAccessPanel(): bool
    {
        return match($this) {
            self::SuperAdmin, self::Admin, self::Editor => true,
            self::Customer => false,
        };
    }

    /** Roles habilitados para el panel admin (excluye Customer) */
    public static function adminRoles(): array
    {
        return [self::SuperAdmin, self::Admin, self::Editor];
    }
}
