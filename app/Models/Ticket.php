<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use Auditable;
    use HasFactory;

    /** Límite máximo de tiempo para SLA (horas). */
    public const SLA_LIMIT_HOURS = 72;

    protected $fillable = [
        'subject',
        'description',
        'area_origin_id',
        'area_current_id',
        'sede_id',
        'ubicacion_id',
        'requester_id',
        'requester_position_id',
        'assigned_user_id',
        'assigned_at',
        'ticket_type_id',
        'priority_id',
        'impact_level_id',
        'urgency_level_id',
        'ticket_state_id',
        'resolved_at',
        'first_response_at',
        'due_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'first_response_at' => 'datetime',
        'due_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    protected $appends = [
        'is_burned',
        'is_overdue',
        'sla_due_at',
        'sla_status_text',
        'first_response_time_text',
    ];

    public function areaOrigin(): BelongsTo { return $this->belongsTo(\App\Models\Area::class, 'area_origin_id'); }
    public function areaCurrent(): BelongsTo { return $this->belongsTo(\App\Models\Area::class, 'area_current_id'); }
    public function sede(): BelongsTo { return $this->belongsTo(\App\Models\Sede::class, 'sede_id'); }
    public function ubicacion(): BelongsTo { return $this->belongsTo(\App\Models\Ubicacion::class, 'ubicacion_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'requester_id'); }
    public function requesterPosition(): BelongsTo { return $this->belongsTo(\App\Models\Position::class, 'requester_position_id'); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'assigned_user_id'); }
    public function ticketType(): BelongsTo { return $this->belongsTo(\App\Models\TicketType::class, 'ticket_type_id'); }
    public function priority(): BelongsTo { return $this->belongsTo(\App\Models\Priority::class, 'priority_id'); }
    public function impactLevel(): BelongsTo { return $this->belongsTo(\App\Models\ImpactLevel::class, 'impact_level_id'); }
    public function urgencyLevel(): BelongsTo { return $this->belongsTo(\App\Models\UrgencyLevel::class, 'urgency_level_id'); }
    public function state(): BelongsTo { return $this->belongsTo(\App\Models\TicketState::class, 'ticket_state_id'); }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(TicketAlert::class);
    }

    /**
     * Scope para el módulo "Mis Tickets": solo tickets donde el usuario es solicitante.
     * No usar en el módulo operativo Tickets.
     */
    public function scopeRequesterOnly(Builder $query, int $userId): Builder
    {
        return $query->where('requester_id', $userId);
    }

    /**
     * Fecha límite para SLA: due_at si existe, si no created_at + 72h.
     */
    public function getSlaDueAtAttribute(): ?Carbon
    {
        if ($this->due_at) {
            return $this->due_at;
        }
        if ($this->created_at) {
            return $this->created_at->copy()->addHours(self::SLA_LIMIT_HOURS);
        }
        return null;
    }

    /**
     * True si el ticket no está resuelto y ya pasó la fecha límite SLA.
     */
    public function getIsOverdueAttribute(): bool
    {
        $isFinal = (bool) ($this->state?->is_final ?? false);
        if ($isFinal) {
            return false;
        }
        $due = $this->sla_due_at;
        return $due ? now()->isAfter($due) : false;
    }

    /**
     * Texto corto para UI: "Vence en X h" / "Vencido hace X h" / "Cerrado".
     */
    public function getSlaStatusTextAttribute(): ?string
    {
        $isFinal = (bool) ($this->state?->is_final ?? false);
        if ($isFinal) {
            return null;
        }
        $due = $this->sla_due_at;
        if (!$due) {
            return null;
        }
        $now = now();
        if ($now->isAfter($due)) {
            $hours = (int) $now->diffInHours($due);
            return $hours <= 0 ? 'Vencido' : "Vencido hace {$hours} h";
        }
        $hours = (int) $now->diffInHours($due, false);
        return $hours <= 0 ? 'Vence pronto' : "Vence en {$hours} h";
    }

    /**
     * Deriva si el ticket está "quemado": pasó el límite SLA y no está cerrado.
     * Usa due_at si existe, si no 72h desde creación.
     */
    public function getIsBurnedAttribute(): bool
    {
        return $this->is_overdue;
    }

    /**
     * Tiempo transcurrido entre created_at y first_response_at en formato legible (ej. "2h 15m", "45m").
     * Null si aún no hay primera respuesta.
     */
    public function getFirstResponseTimeTextAttribute(): ?string
    {
        if (!$this->first_response_at || !$this->created_at) {
            return null;
        }
        $created = $this->created_at instanceof Carbon ? $this->created_at : Carbon::parse($this->created_at);
        $firstResponse = $this->first_response_at instanceof Carbon ? $this->first_response_at : Carbon::parse($this->first_response_at);
        $totalMinutes = (int) $created->diffInMinutes($firstResponse);
        if ($totalMinutes < 1) {
            return '<1m';
        }
        $days = (int) floor($totalMinutes / 1440);
        $hours = (int) floor(($totalMinutes % 1440) / 60);
        $minutes = (int) ($totalMinutes % 60);
        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = $minutes . 'm';
        }
        return implode(' ', $parts);
    }
}
