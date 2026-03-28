<?php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'cpf',
        'phone',
        'role',
        'barbershop_id',
        'trial_ends_at',
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
            'cpf'               => 'encrypted',
            'phone'             => 'encrypted',
            'trial_ends_at'     => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Filament: Controle de acesso ao painel
    // -------------------------------------------------------------------------

    /** Apenas admin e barber entram no painel Filament.
     *  Novos usuários (role=client sem barbearia) também entram para completar o cadastro.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (in_array($this->role, ['admin', 'barber'])) {
            return true;
        }

        // Permite acesso para usuários recém-cadastrados completarem o registro da barbearia
        if ($this->role === 'client' && $this->ownedBarbershops()->doesntExist()) {
            return true;
        }

        return false;
    }

    // -------------------------------------------------------------------------
    // Filament: Multi-Tenancy (HasTenants)
    // -------------------------------------------------------------------------

    /**
     * Retorna a lista de tenants (barbearias) que o usuário pode acessar.
     * - admin → todas as barbearias do sistema
     * - barber → somente as barbearias que ele é dono
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->isAdmin()) {
            return Barbershop::all();
        }

        return $this->ownedBarbershops;
    }

    /**
     * Verifica se o usuário pode acessar um tenant específico.
     * - admin → pode acessar qualquer tenant
     * - barber → apenas os seus próprios
     */
    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->ownedBarbershops()->where('id', $tenant->id)->exists();
    }

    // -------------------------------------------------------------------------
    // Relacionamentos
    // -------------------------------------------------------------------------

    /** Barbearias que este usuário é DONO (creator). */
    public function ownedBarbershops(): HasMany
    {
        return $this->hasMany(Barbershop::class, 'user_id');
    }

    /** Agendamentos vinculados ao usuário como cliente. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }

    /** Assinatura ativa do usuário-cliente (planos de corte). */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>=', now());
    }

    // -------------------------------------------------------------------------
    // Helpers de Role
    // -------------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isBarber(): bool
    {
        return $this->role === 'barber';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }
}

