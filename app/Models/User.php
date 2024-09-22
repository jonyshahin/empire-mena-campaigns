<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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

    protected $with = ['roles'];

    protected $guard_name = 'sanctum';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = [
        'user_is_active',
    ];

    public function getUserIsActiveAttribute()
    {
        //check if updated_at is less than 15 mins
        $promoter_point = $this->promoterPoint;
        if (!$promoter_point) {
            return false;
        } elseif ($promoter_point->updated_at->greaterThan(now()->subMinutes(15))) {
            return true;
        }
        return false;
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function consumers(): HasMany
    {
        return $this->hasMany(Consumer::class);
    }

    public function promoterPoint(): HasOne
    {
        return $this->hasOne(PromoterTracking::class);
    }

    public function company()
    {
        return $this->hasOneThrough(Client::class, CompanyUser::class, 'user_id', 'id', 'id', 'client_id');
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_promoter')->without(['promoters']);
    }

    public function team_leader_campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_team_leader', 'team_leader_id', 'campaign_id')->without(['team_leaders']);
    }
}
