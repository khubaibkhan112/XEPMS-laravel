<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'position',
        'department',
        'property_id',
        'notes',
        'is_active',
        'last_login_at',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get the default property for this user
     */
    public function defaultProperty()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get roles for this user
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('property_id')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleSlug, ?int $propertyId = null): bool
    {
        $query = $this->roles()->where('slug', $roleSlug)->where('is_active', true);
        
        if ($propertyId !== null) {
            $query->wherePivot('property_id', $propertyId);
        }
        
        return $query->exists();
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionSlug, ?int $propertyId = null): bool
    {
        // Check if user has permission through any of their roles
        $query = $this->roles()
            ->where('is_active', true)
            ->whereHas('permissions', function ($q) use ($permissionSlug) {
                $q->where('slug', $permissionSlug);
            });

        if ($propertyId !== null) {
            $query->wherePivot('property_id', $propertyId);
        }

        return $query->exists();
    }

    /**
     * Get all permissions for this user (through roles)
     */
    public function permissions(?int $propertyId = null)
    {
        $roleQuery = $this->roles()->where('is_active', true);
        
        if ($propertyId !== null) {
            $roleQuery->wherePivot('property_id', $propertyId);
        }

        $roleIds = $roleQuery->pluck('roles.id');

        return Permission::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })->distinct();
    }
}
