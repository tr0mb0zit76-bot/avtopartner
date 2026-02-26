<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'site_id',
        'theme',
        'role_id',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];

    /**
     * Get the site that the user belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the role that the user belongs to.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is supervisor.
     */
    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    /**
     * Check if user is manager.
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user is accountant.
     */
    public function isAccountant(): bool
    {
        return $this->hasRole('accountant');
    }

    /**
     * Check if user is dispatcher.
     */
    public function isDispatcher(): bool
    {
        return $this->hasRole('dispatcher');
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->role) {
            return false;
        }
        
        // Получаем права роли
        $permissions = $this->role->permissions;
        
        // Если прав нет
        if (empty($permissions)) {
            return false;
        }
        
        // Декодируем JSON
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }
        
        // Если не массив или пусто
        if (!is_array($permissions) || empty($permissions)) {
            return false;
        }
        
        // Если есть '*' (все права)
        if (in_array('*', $permissions)) {
            return true;
        }
        
        // Проверяем конкретное право
        return in_array($permission, $permissions);
    }

    /**
     * Check if user can do action on specific order.
     */
    public function canAccessOrder(Order $order): bool
    {
        // Админ может всё
        if ($this->isAdmin()) {
            return true;
        }
        
        // Руководитель может всё
        if ($this->isSupervisor()) {
            return true;
        }
        
        // Менеджер только свои заявки
        if ($this->isManager()) {
            return $order->manager_id === $this->id;
        }
        
        // Бухгалтер может всё (финансы)
        if ($this->isAccountant()) {
            return true;
        }
        
        // Диспетчер может всё (распределение)
        if ($this->isDispatcher()) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the user's theme.
     */
    public function getThemeAttribute($value)
    {
        return $value ?: 'light';
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include users by role.
     */
    public function scopeByRole($query, string $roleName)
    {
        return $query->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Get users with specific permission.
     */
    public function scopeWithPermission($query, string $permission)
    {
        return $query->whereHas('role', function ($q) use ($permission) {
            $q->whereJsonContains('permissions', $permission)
              ->orWhereJsonContains('permissions', '*');
        });
    }
}