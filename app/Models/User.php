<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'salon_name',
        'address',
        'avatar',
        'role',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relations
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function sales()
{
    return $this->hasMany(Sale::class);
}

      public function getAvatarAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // Si c'est déjà une URL complète (http:// ou https://), la retourner telle quelle
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        
        // Sinon, construire l'URL complète vers storage/public
        return url('storage/' . $value);
    }

    /**
     * Mutator pour sauvegarder seulement le chemin relatif
     */
    public function setAvatarAttribute($value)
    {
        // Si c'est une URL complète, extraire seulement le chemin
        if (str_starts_with($value, url('storage/'))) {
            $value = str_replace(url('storage/'), '', $value);
        }
        
        $this->attributes['avatar'] = $value;
    }
}