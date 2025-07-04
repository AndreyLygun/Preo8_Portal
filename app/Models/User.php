<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Orchid\Filters\Types\Like;
use Orchid\Filters\Types\Where;
use Orchid\Filters\Types\WhereDateStartEnd;
use Orchid\Platform\Models\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'permissions',
        'drx_account_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'permissions',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'permissions' => 'array',
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes for which you can use filters in url.
     *
     * @var array
     */
    protected $allowedFilters = [
        'id' => Where::class,
        'name' => Like::class,
        'email' => Like::class,
        'updated_at' => WhereDateStartEnd::class,
        'created_at' => WhereDateStartEnd::class,
    ];

    /**
     * The attributes for which can use sort in url.
     *
     * @var array
     */
    protected $allowedSorts = [
        'id',
        'name',
        'email',
        'updated_at',
        'created_at',
    ];

    public function DrxAccount()
    {
        return $this->belongsTo(DrxAccount::class);
    }

    public function scopeSameRenter(Builder $query)
    {
        $currentUser = auth()->user();
        $query->where('drx_account_id', $currentUser['drx_account_id']);
    }

    protected static function booted(): void
    {
        if (Auth::user() && !Auth::user()->hasAccess('platform.portal.renters')) {
            $renter_drx_id = Auth::user()['drx_account_id'];
            static::addGlobalScope('renter', function (Builder $builder) use ($renter_drx_id) {
                $builder->where('drx_account_id', $renter_drx_id);
            });
        }
    }
}
