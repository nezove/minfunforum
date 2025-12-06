<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем админа если его нет
        $admin = User::where('email', 'admin@forum.com')->first();
        if (!$admin) {
            $admin = User::create([
                'username' => 'admin',
                'name' => 'Администратор',
                'email' => 'admin@forum.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Создан администратор: admin@forum.com / password');
        } else {
            // Обновляем существующего пользователя до админа
            $admin->update(['role' => 'admin']);
            $this->command->info('Пользователь admin@forum.com назначен администратором');
        }

        // Создаем модератора если его нет
        $moderator = User::where('email', 'moderator@forum.com')->first();
        if (!$moderator) {
            $moderator = User::create([
                'username' => 'moderator',
                'name' => 'Модератор',
                'email' => 'moderator@forum.com',
                'password' => Hash::make('password'),
                'role' => 'moderator',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Создан модератор: moderator@forum.com / password');
        } else {
            // Обновляем существующего пользователя до модератора
            $moderator->update(['role' => 'moderator']);
            $this->command->info('Пользователь moderator@forum.com назначен модератором');
        }

        // Обновляем всех остальных пользователей до роли 'user' если у них нет роли
        User::whereNull('role')->orWhere('role', '')->update(['role' => 'user']);
        
        $this->command->info('Всем пользователям без роли назначена роль "Пользователь"');
    }
}