<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory;

    /**
     * Mass assignment deshabilitado a propósito: no se define $fillable hasta la
     * revisión de Form Requests/API. El trait Notifiable se omitió a propósito;
     * la relación notifications() se declara explícitamente sobre el modelo propio.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function requestedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by_id');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to_id');
    }

    public function ticketEvents(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'actor_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'author_id');
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(TicketIntervention::class, 'technician_id');
    }

    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by_id');
    }

    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(TicketRating::class, 'user_id');
    }

    public function ratingsAsTechnician(): HasMany
    {
        return $this->hasMany(TicketRating::class, 'technician_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function appliedAiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'applied_by_id');
    }

    public function editedSettings(): HasMany
    {
        return $this->hasMany(Setting::class, 'updated_by_id');
    }

    public function assignedAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'assigned_user_id');
    }

    public function diagnosticAnswers(): HasMany
    {
        return $this->hasMany(TicketDiagnosticAnswer::class, 'answered_by_id');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
