<?php

namespace App\Models;

use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionalDocument extends Model
{
    use SoftDeletes, TracksUpdatedBy;

    protected $fillable = [
        'entity_id',
        'document_type',
        'subject',
        'document_date',
        'status',
        'delivery_date',
        'requires_follow_up',
        'follow_up_date',
        'observation',
        'has_physical_support',
        'physical_location',
        'main_file_path',
        'attachments',
        'manager_id',
        'updated_by_id',
    ];

    protected $casts = [
        'document_date'      => 'date',
        'delivery_date'      => 'date',
        'follow_up_date'     => 'date',
        'requires_follow_up' => 'boolean',
        'attachments'        => 'array',
    ];

    const DOCUMENT_TYPE_OPTIONS = [
        'carta'            => 'Carta',
        'oficio'           => 'Oficio',
        'presentacion'     => 'Presentación',
        'solicitud_apoyo'  => 'Solicitud de apoyo',
        'certificado_pdm'  => 'Certificado PDM',
        'carta_intencion'  => 'Carta de intención',
        'lista_asistencia' => 'Lista de asistencia',
        'acta'             => 'Acta',
        'informe'          => 'Informe',
        'base_datos'       => 'Base de datos',
        'otro'             => 'Otro',
    ];

    const STATUS_OPTIONS = [
        'pending'           => 'Pendiente',
        'in_management'     => 'En gestión',
        'delivered'         => 'Entregado',
        'pending_signature' => 'Pendiente de firma',
        'signed'            => 'Firmado',
        'finalized'         => 'Finalizado',
    ];

    const STATUS_COLORS = [
        'pending'           => 'warning',
        'in_management'     => 'info',
        'delivered'         => 'primary',
        'pending_signature' => 'danger',
        'signed'            => 'success',
        'finalized'         => 'success',
    ];

    const PHYSICAL_SUPPORT_OPTIONS = [
        'yes'           => 'Sí',
        'no'            => 'No',
        'not_applicable' => 'No aplica',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'entity_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? $this->status;
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPE_OPTIONS[$this->document_type] ?? $this->document_type;
    }

    public function hasFile(): bool
    {
        return !empty($this->main_file_path);
    }

    public function isFollowUpOverdue(): bool
    {
        return $this->requires_follow_up
            && $this->follow_up_date
            && $this->follow_up_date->isPast()
            && !in_array($this->status, ['signed', 'finalized']);
    }
}