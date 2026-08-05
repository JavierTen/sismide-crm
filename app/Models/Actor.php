<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Actor extends Model
{
    use SoftDeletes, TracksUpdatedBy;

    protected $fillable = [
        // Información General
        'name',
        'type',
        'type_other',

        // Identificación de la entidad
        'nit',
        'nature',
        'economic_sector',
        'institutional_phone',
        'institutional_email',
        'website',
        'description',
        'linkage_status',

        // Contacto Principal (legacy — datos históricos)
        'contact_name',
        'contact_role',
        'contact_email',
        'contact_phone',

        // Ubicación y Accesibilidad
        'has_physical_office',
        'office_address',
        'city_id',
        'department_id',
        'main_location',
        'office_hours',

        // Cobertura territorial
        'territorial_coverage',
        'attention_modality',

        // Georreferenciación
        'latitude',
        'longitude',
        'georeference_photo_path',

        // Áreas de aporte
        'contribution_areas',
        'contribution_areas_other',

        // Experiencia previa
        'has_entrepreneurship_experience',
        'entrepreneurship_experience_details',
        'experience_population_type',
        'experience_economic_sectors',
        'experience_territories',
        'experience_entrepreneurships_count',
        'experience_evidence_path',

        // Compromisos con Ruta D
        'commitments',
        'commitments_other',
        'commitments_confirmed',
        'commitment_types',
        'commitment_description',
        'commitment_responsible_contact',
        'commitment_routes',
        'commitment_municipalities',
        'commitment_modality',
        'commitment_estimated_count',
        'commitment_observations',

        // Utilidad Estratégica — Conexión con mercados
        'market_connection_enabled',
        'market_connection_types',
        'market_connection',

        // Utilidad Estratégica — Gestiones con autoridades
        'authority_management_enabled',
        'authority_management',
        'authority_management_entities',
        'authority_management_routes',

        // Utilidad Estratégica — Acceso a financiación
        'financing_access_enabled',
        'financing_types',
        'financing_access',

        // Utilidad Estratégica — Capacitación, mentoría o asesoría
        'training_advisory_enabled',
        'training_service_types',
        'training_advisory',
        'training_modality',
        'training_capacity_count',

        // Utilidad Estratégica — Apoyo logístico
        'logistic_support_enabled',
        'logistic_support_types',
        'logistic_support',
        'logistic_support_coverage',

        // Utilidad Estratégica — Fortalecimiento organizacional
        'organizational_strengthening_enabled',
        'organizational_strengthening_desc',

        // Utilidad Estratégica — Innovación y digital
        'innovation_digital_enabled',
        'innovation_digital_desc',

        // Articulación por rutas
        'route_1_enabled',
        'route_1_support_types',
        'route_2_enabled',
        'route_2_support_types',
        'route_3_enabled',
        'route_3_support_types',

        // Valor Diferencial
        'action_scope',

        // Manager
        'manager_id',
        'updated_by_id',
    ];

    protected $casts = [
        'has_physical_office'              => 'boolean',
        'has_entrepreneurship_experience'  => 'boolean',
        'contribution_areas'               => 'array',
        'territorial_coverage'             => 'array',
        'attention_modality'               => 'array',
        'experience_population_type'       => 'array',
        'experience_economic_sectors'      => 'array',
        'experience_territories'           => 'array',
        'commitment_types'                 => 'array',
        'commitment_routes'                => 'array',
        'commitment_municipalities'        => 'array',
        'market_connection_enabled'        => 'boolean',
        'market_connection_types'          => 'array',
        'authority_management_enabled'     => 'boolean',
        'authority_management_routes'      => 'array',
        'financing_access_enabled'         => 'boolean',
        'financing_types'                  => 'array',
        'training_advisory_enabled'        => 'boolean',
        'training_service_types'           => 'array',
        'logistic_support_enabled'         => 'boolean',
        'logistic_support_types'           => 'array',
        'organizational_strengthening_enabled' => 'boolean',
        'innovation_digital_enabled'       => 'boolean',
        'route_1_enabled'                  => 'boolean',
        'route_1_support_types'            => 'array',
        'route_2_enabled'                  => 'boolean',
        'route_2_support_types'            => 'array',
        'route_3_enabled'                  => 'boolean',
        'route_3_support_types'            => 'array',
        'latitude'                         => 'decimal:8',
        'longitude'                        => 'decimal:8',
        'created_at'                       => 'datetime',
        'updated_at'                       => 'datetime',
        'deleted_at'                       => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new YearColumnScope('created_at'));

        static::updating(function ($actor) {
            if ($actor->isDirty('georeference_photo_path')) {
                $oldPhoto = $actor->getOriginal('georeference_photo_path');
                if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
                    Storage::disk('public')->delete($oldPhoto);
                }
            }
        });

        static::forceDeleting(function ($actor) {
            if ($actor->georeference_photo_path && Storage::disk('public')->exists($actor->georeference_photo_path)) {
                Storage::disk('public')->delete($actor->georeference_photo_path);
            }
        });
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(EntityContact::class, 'entity_id');
    }

    // ─── Constantes ───────────────────────────────────────────────────────────

    public const TYPE_OPTIONS = [
        'private_company'         => 'Empresa privada',
        'commercial'              => 'Comercial',
        'guild_association'       => 'Asociación gremial',
        'government_authority'    => 'Autoridad gubernamental',
        'educational_institution' => 'Institución educativa',
        'ngo_foundation'          => 'ONG / Fundación',
        'financial_entity'        => 'Entidad financiera',
        'other'                   => 'Otro',
    ];

    public const NATURE_OPTIONS = [
        'public'              => 'Pública',
        'private'             => 'Privada',
        'mixed'               => 'Mixta',
        'academia'            => 'Academia',
        'social_organization' => 'Organización social',
        'cooperation'         => 'Cooperación',
        'guild_association'   => 'Gremio o asociación',
    ];

    public const LINKAGE_STATUS_OPTIONS = [
        'identified'        => 'Identificada',
        'pending_contact'   => 'Pendiente de contactar',
        'contacted'         => 'Contactada',
        'in_management'     => 'En gestión',
        'linked'            => 'Vinculada',
        'active_commitment' => 'Con compromiso activo',
        'inactive'          => 'Inactiva',
    ];

    public const TERRITORIAL_COVERAGE_OPTIONS = [
        'algarrobo'       => 'Algarrobo',
        'aracataca'       => 'Aracataca',
        'fundacion'       => 'Fundación',
        'zona_bananera'   => 'Zona Bananera',
        'cienaga'         => 'Ciénaga',
        'other_magdalena' => 'Otros municipios del Magdalena',
        'departmental'    => 'Cobertura departamental',
        'regional'        => 'Cobertura regional',
        'national'        => 'Cobertura nacional',
        'international'   => 'Cobertura internacional',
    ];

    public const ATTENTION_MODALITY_OPTIONS = [
        'presential' => 'Presencial',
        'virtual'    => 'Virtual',
        'hybrid'     => 'Híbrida',
    ];

    public const CONTRIBUTION_AREAS_OPTIONS = [
        // Opciones originales
        'institutional_support'       => 'Apoyo institucional',
        'training_capacity'           => 'Formación y fortalecimiento de capacidades',
        'financial_support'           => 'Apoyo financiero',
        'commercialization'           => 'Comercialización',
        'communication_dissemination' => 'Comunicación y difusión',
        'network_linkage'             => 'Vinculación a redes o ecosistemas',
        // Nuevas opciones del documento
        'business_mentoring'          => 'Mentorías empresariales',
        'technical_advisory'          => 'Asesorías técnicas',
        'organizational_strengthening'=> 'Fortalecimiento organizacional',
        'innovation_digital'          => 'Innovación y transformación digital',
        'new_markets'                 => 'Acceso a nuevos mercados',
        'business_expansion'          => 'Expansión empresarial',
        'logistic_support'            => 'Apoyo logístico',
        'other'                       => 'Otro',
    ];

    public const EXPERIENCE_POPULATION_TYPE_OPTIONS = [
        'rural'      => 'Rural',
        'urban'      => 'Urbano',
        'youth'      => 'Jóvenes',
        'women'      => 'Mujeres',
        'indigenous' => 'Comunidades indígenas',
        'afro'       => 'Afrodescendientes',
        'displaced'  => 'Víctimas / Desplazados',
        'veterans'   => 'Excombatientes',
        'disabled'   => 'Personas con discapacidad',
    ];

    public const EXPERIENCE_ECONOMIC_SECTORS_OPTIONS = [
        'agriculture'  => 'Agropecuario',
        'agroindustry' => 'Agroindustria',
        'commerce'     => 'Comercio',
        'services'     => 'Servicios',
        'tourism'      => 'Turismo',
        'technology'   => 'Tecnología e innovación',
        'crafts'       => 'Artesanías',
        'fishing'      => 'Pesca',
        'construction' => 'Construcción',
        'other'        => 'Otro',
    ];

    // Legacy — para exportación de datos históricos
    public const COMMITMENTS_OPTIONS = [
        'financial_resources' => 'Aportar recursos financieros',
        'in_kind_resources'   => 'Aportar recursos en especie',
        'training_technical'  => 'Brindar formación / asistencia técnica',
        'strategic_contacts'  => 'Facilitar contactos estratégicos',
        'other'               => 'Otro',
    ];

    public const COMMITMENTS_CONFIRMED_OPTIONS = [
        'yes'     => 'Sí',
        'no'      => 'No',
        'pending' => 'Pendiente de confirmación',
    ];

    public const COMMITMENT_TYPES_OPTIONS = [
        'financial_resources'    => 'Aportar recursos financieros',
        'in_kind_resources'      => 'Aportar recursos en especie',
        'provide_training'       => 'Brindar formación',
        'provide_mentoring'      => 'Brindar mentorías',
        'technical_advisory'     => 'Brindar asesorías técnicas',
        'strategic_contacts'     => 'Facilitar contactos estratégicos',
        'commercial_connections' => 'Facilitar conexiones comerciales',
        'financing_opportunities'=> 'Facilitar oportunidades de financiación',
        'business_rounds'        => 'Participar en ruedas de negocio',
        'networking'             => 'Participar en espacios de networking',
        'spaces_infrastructure'  => 'Facilitar espacios o infraestructura',
        'logistic_support'       => 'Brindar apoyo logístico',
        'other'                  => 'Otro',
    ];

    public const COMMITMENT_ROUTES_OPTIONS = [
        'route_1' => 'Ruta 1 — Preemprendimiento y Validación Temprana',
        'route_2' => 'Ruta 2 — Consolidación Empresarial',
        'route_3' => 'Ruta 3 — Escalamiento y Expansión Empresarial',
    ];

    public const MARKET_CONNECTION_TYPES_OPTIONS = [
        'local'       => 'Local',
        'regional'    => 'Regional',
        'national'    => 'Nacional',
        'international' => 'Internacional',
        'institutional' => 'Institucional',
        'ecommerce'   => 'Comercio electrónico',
    ];

    public const FINANCING_TYPES_OPTIONS = [
        'credit'           => 'Crédito',
        'seed_capital'     => 'Capital semilla',
        'calls'            => 'Convocatorias',
        'private_investment'=> 'Inversión privada',
        'investment_funds' => 'Fondos de inversión',
        'non_reimbursable' => 'Recursos no reembolsables',
        'other'            => 'Otro',
    ];

    public const TRAINING_SERVICE_TYPES_OPTIONS = [
        'workshop'           => 'Taller',
        'training'           => 'Capacitación',
        'individual_mentoring'=> 'Mentoría individual',
        'group_mentoring'    => 'Mentoría grupal',
        'technical_advisory' => 'Asesoría técnica',
        'consulting'         => 'Consultoría',
    ];

    public const LOGISTIC_SUPPORT_TYPES_OPTIONS = [
        'spaces'        => 'Espacios',
        'equipment'     => 'Equipos',
        'transport'     => 'Transporte',
        'connectivity'  => 'Conectividad',
        'materials'     => 'Materiales',
        'dissemination' => 'Difusión',
        'other'         => 'Otro',
    ];

    public const ROUTE_1_SUPPORT_TYPES_OPTIONS = [
        'group_mentoring'      => 'Mentorías grupales',
        'idea_validation'      => 'Validación de ideas',
        'commercial_validation'=> 'Validación comercial',
        'prototype_development'=> 'Desarrollo o evolución de prototipos',
        'technical_monitoring' => 'Seguimiento técnico',
        'market_entry'         => 'Preparación para entrada al mercado',
        'other'                => 'Otro',
    ];

    public const ROUTE_2_SUPPORT_TYPES_OPTIONS = [
        'specialized_mentoring'        => 'Mentorías especializadas',
        'individual_technical_advisory'=> 'Asesorías técnicas individuales',
        'administrative_strengthening' => 'Fortalecimiento administrativo',
        'financial_strengthening'      => 'Fortalecimiento financiero',
        'commercial_strengthening'     => 'Fortalecimiento comercial',
        'process_improvement'          => 'Mejoramiento de procesos internos',
        'networking'                   => 'Networking',
        'business_rounds'              => 'Ruedas de negocio',
        'financing_preparation'        => 'Preparación para oportunidades de financiación',
        'other'                        => 'Otro',
    ];

    public const ROUTE_3_SUPPORT_TYPES_OPTIONS = [
        'strategic_mentoring'      => 'Mentoría estratégica',
        'pitch_strengthening'      => 'Fortalecimiento de pitch empresarial',
        'investment_preparation'   => 'Preparación para inversión',
        'individual_monitoring'    => 'Seguimiento individualizado',
        'commercial_expansion'     => 'Expansión comercial',
        'new_markets'              => 'Acceso a nuevos mercados',
        'investor_connection'      => 'Conexión con inversionistas',
        'financing_access'         => 'Acceso a financiación',
        'innovation_competitiveness'=> 'Innovación y competitividad',
        'strategic_alliances'      => 'Alianzas estratégicas',
        'business_networking'      => 'Networking empresarial',
        'business_rounds'          => 'Ruedas de negocio',
        'internationalization'     => 'Internacionalización',
        'other'                    => 'Otro',
    ];

    public const ACTION_SCOPE_OPTIONS = [
        'local'         => 'Local (solo municipio)',
        'regional'      => 'Regional (Magdalena u otros municipios)',
        'national'      => 'Nacional',
        'international' => 'Internacional',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeByManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeWithPhysicalOffice($query)
    {
        return $query->where('has_physical_office', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByActionScope($query, string $scope)
    {
        return $query->where('action_scope', $scope);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getTypeNameAttribute(): string
    {
        return self::TYPE_OPTIONS[$this->type] ?? $this->type;
    }

    public function getActionScopeNameAttribute(): string
    {
        return self::ACTION_SCOPE_OPTIONS[$this->action_scope] ?? $this->action_scope;
    }
}
