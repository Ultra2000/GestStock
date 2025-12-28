<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TestAuth extends Command
{
    protected $signature = 'test:auth';
    protected $description = 'Test authentication system';

    public function handle()
    {
        $this->info('Testing authentication system...');
        
        // Test user creation/retrieval
        $user = User::where('email', 'admin@example.com')->first();
        
        if (!$user) {
            $this->error('Admin user not found');
            return;
        }
        
        $this->info("User found: {$user->name} ({$user->email}) - Role: {$user->role}");
        
        // Test password verification
        $testPassword = 'password';
        if (Hash::check($testPassword, $user->password)) {
            $this->info('Password verification: SUCCESS');
        } else {
            $this->error('Password verification: FAILED');
        }
        
        // Test manual authentication
        if (Auth::attempt(['email' => 'admin@example.com', 'password' => $testPassword])) {
            $this->info('Manual authentication: SUCCESS');
            $this->info('Authenticated user: ' . Auth::user()->name);
        } else {
            $this->error('Manual authentication: FAILED');
        }
        
        return 0;
    }
}
