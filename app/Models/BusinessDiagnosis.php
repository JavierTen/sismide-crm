<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusinessDiagnosis extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entrepreneur_id',
        'diagnosis_date',
        'has_news',
        'news_type',
        'news_date',
        'administrative_section',
        'financial_section',
        'production_section',
        'market_section',
        'technology_section',
        'general_observations',
        'work_sections',
    ];

    protected $casts = [
        'diagnosis_date' => 'date',
        'news_date' => 'date',
        'has_news' => 'boolean',
        'administrative_section' => 'array',
        'financial_section' => 'array',
        'production_section' => 'array',
        'market_section' => 'array',
        'technology_section' => 'array',
        'work_sections' => 'array',
    ];

    /**
     * Get the entrepreneur that owns the diagnosis.
     */
    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class)->withTrashed();
    }

    /**
     * Opciones para tipos de novedad
     */
    public static function newsTypeOptions(): array
    {
        return [
            'reactivation' => 'Reactivación',
            'definitive_closure' => 'Cierre de Emprendimiento definitivo',
            'temporary_closure' => 'Cierre de Emprendimiento temporal',
            'permanent_disability' => 'Incapacidad Permanente',
            'temporary_disability' => 'Incapacidad Temporal',
            'definitive_retirement' => 'Retiro definitivo',
            'temporary_retirement' => 'Retiro temporal',
            'address_change' => 'Cambio de domicilio',
            'owner_death' => 'Muerte del titular',
            'no_news' => 'Sin novedad',
        ];
    }

    /**
     * Opciones para secciones de trabajo
     */
    public static function workSectionOptions(): array
    {
        return [
            'administrative' => 'Sección Administrativa',
            'financial' => 'Sección Financiera y Contable',
            'production' => 'Sección De Producción',
            'market' => 'Sección De Mercado y comercial',
            'technology' => 'Sección Digital Tecnología',
        ];
    }

    /**
     * Preguntas sección administrativa
     */
    public static function administrativeQuestions(): array
    {
        return [
            'task_organization' => [
                'question' => '¿Cómo organizas las tareas y responsabilidades en tu emprendimiento?',
                'options' => [
                    'no_organization' => 'No tengo una organización definida de tareas',
                    'informal' => 'Organizo tareas de manera informal o verbal',
                    'basic_list' => 'Tengo una lista básica de tareas y responsabilidades',
                    'not_applicable' => 'No lo hago o No Aplica'
                ]
            ],
            'resource_planning' => [
                'question' => '¿Cómo planificas las necesidades de recursos para tu negocio?',
                'options' => [
                    'no_planning' => 'No realizo planificación de recursos',
                    'basic_plan' => 'Tengo un plan básico basado en experiencias pasadas',
                    'planning_tools' => 'Uso herramientas de planificación para prever necesidades futuras',
                    'not_applicable' => 'No lo hago o No Aplica'
                ]
            ],
            'communication_channels' => [
                'question' => '¿Cómo se manejan los canales de comunicación con clientes y proveedores?',
                'options' => [
                    'irregular' => 'La comunicación es irregular y solo cuando es necesario',
                    'periodic_traditional' => 'Mantengo comunicación periódica a través de métodos tradicionales',
                    'digital_regular' => 'Utilizo medios digitales para comunicarme regularmente',
                    'not_applicable' => 'No lo hago o No Aplica'
                ]
            ],
            'purchase_management' => [
                'question' => '¿Cómo se realiza la gestión de compras y adquisiciones?',
                'options' => [
                    'basic_tracking' => 'Realizo un seguimiento básico de las necesidades de compra',
                    'planned_system' => 'Tengo un sistema de gestión de compras planificado',
                    'advanced_software' => 'Utilizo métodos avanzados y software especializado para la gestión de compras',
                    'not_applicable' => 'No lo hago o No Aplica'
                ]
            ],
            'distribution' => [
                'question' => '¿Cómo es la distribución de sus productos o servicios?',
                'options' => [
                    'self' => 'Usted mismo',
                    'outsourced' => 'Subcontrato a un tercero',
                    'not_applicable' => 'No lo hago o No Aplica'
                ]
            ]
        ];
    }

    /**
     * Check if diagnosis is complete
     */
    public function isComplete(): bool
    {
        return !empty($this->administrative_section) &&
               !empty($this->financial_section) &&
               !empty($this->production_section) &&
               !empty($this->market_section) &&
               !empty($this->technology_section) &&
               !empty($this->work_sections) &&
               count($this->work_sections) >= 2;
    }
}
