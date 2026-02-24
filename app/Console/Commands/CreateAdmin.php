<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->error('Role "admin" not found. Run seeders first: php artisan db:seed --class=RolesTableSeeder');
            return 1;
        }
        
        $email = $this->argument('email');
        if (!$email) {
            $email = $this->ask('Enter admin email');
        }
        
        $name = $this->ask('Enter admin name', 'Admin');
        
        $password = $this->secret('Enter password');
        $passwordConfirmation = $this->secret('Confirm password');
        
        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match!');
            return 1;
        }
        
        // Проверяем, не существует ли уже такой email
        if (User::where('email', $email)->exists()) {
            $this->error('User with this email already exists!');
            return 1;
        }
        
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
        
        $this->info('✅ Admin user created successfully!');
        $this->table(['Field', 'Value'], [
            ['ID', $user->id],
            ['Email', $user->email],
            ['Name', $user->name],
            ['Role', $adminRole->display_name],
        ]);
        
        return 0;
    }
}
