<?php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
// 1. IMPORTAÇÃO OBRIGATÓRIA PARA API

class User extends Authenticatable implements FilamentUser
{
    // 2. ADICIONE O TRAIT HasApiTokens AQUI PARA REMOVER O ERRO 500
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Permissões de acesso ao Painel Admin (Filament)
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Só permite acesso se o usuário tiver a role 'admin' ou 'barber'
        return in_array($this->role, ['admin', 'barber']);
    }

    /**
     * Campos que podem ser preenchidos em massa
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // 3. ADICIONE 'role' AQUI PARA CONSEGUIR SALVAR NO BANCO
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function barbershops()
    {
        return $this->hasMany(Barbershop::class);
    }

    // Dentro da classe User
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>=', now());
    }
}
