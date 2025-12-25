<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntrepreneurPathResource\Pages;
use App\Models\Entrepreneur;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EntrepreneurPathResource extends Resource
{
    protected static ?string $model = Entrepreneur::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Emprendedores';

    protected static ?string $modelLabel = 'Ruta del Emprendedor';

    protected static ?string $pluralModelLabel = 'Ruta del Emprendedor';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('status', true)
            ->with([
                'business',
                'city',
                'manager',
                'visits',
                'characterizations.economicActivity',
                'characterizations.manager',
                'businessPlan',
                'fairEvaluations.fair',
                'pqrfs',
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * Renderizar botones de archivos
     */
    private static function renderFileButtons($files, string $label = 'Ver archivo'): string
    {
        if (!$files) {
            return 'Sin evidencia';
        }

        if (!is_array($files)) {
            $files = json_decode($files, true) ?? [$files];
        }

        if (empty($files)) {
            return 'Sin evidencia';
        }

        $html = '<div class="space-y-2">';

        foreach ($files as $index => $file) {
            $url = Storage::url($file);

            $html .= '<a href="' . $url . '" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition">';
            $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
            $html .= '<span>' . $label . ' ' . ($index + 1) . '</span>';
            $html .= '</a>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('full_name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Emprendedor')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Emprendimiento')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('Sin emprendimiento'),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Gestor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin gestor asignado'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city_id')
                    ->label('Municipio')
                    ->relationship('city', 'name', function ($query) {
                        return $query->where('status', true)
                            ->whereHas('department', function ($q) {
                                $q->where('status', true);
                            })
                            ->orderBy('name', 'asc');
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('manager_id')
                    ->label('Gestor')
                    ->relationship('manager', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver información')
                    ->modalHeading('Información del Emprendedor')
                    ->modalWidth('7xl')
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Información Personal y del Emprendimiento')
                            ->icon('heroicon-o-information-circle')
                            ->iconColor('warning')
                            ->schema([
                                \Filament\Infolists\Components\Grid::make(2)
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('full_name')
                                            ->label('Nombre completo')
                                            ->icon('heroicon-o-user')
                                            ->iconColor('warning')
                                            ->placeholder('Sin nombre'),

                                        \Filament\Infolists\Components\TextEntry::make('document_number')
                                            ->label('Número de documento')
                                            ->icon('heroicon-o-credit-card')
                                            ->iconColor('warning')
                                            ->placeholder('Sin documento'),

                                        \Filament\Infolists\Components\TextEntry::make('phone')
                                            ->label('Teléfono')
                                            ->icon('heroicon-o-phone')
                                            ->iconColor('success')
                                            ->placeholder('Sin teléfono'),

                                        \Filament\Infolists\Components\TextEntry::make('email')
                                            ->label('Correo electrónico')
                                            ->icon('heroicon-o-envelope')
                                            ->iconColor('info')
                                            ->placeholder('Sin email'),

                                        \Filament\Infolists\Components\TextEntry::make('city.name')
                                            ->label('Municipio / Zona de atención')
                                            ->icon('heroicon-o-map-pin')
                                            ->iconColor('danger')
                                            ->placeholder('Sin ubicación'),

                                        \Filament\Infolists\Components\TextEntry::make('business.business_name')
                                            ->label('Nombre del emprendimiento')
                                            ->icon('heroicon-o-building-storefront')
                                            ->iconColor('primary')
                                            ->placeholder('Sin emprendimiento'),

                                        \Filament\Infolists\Components\TextEntry::make('business.economicActivity.name')
                                            ->label('Sector productivo')
                                            ->icon('heroicon-o-briefcase')
                                            ->iconColor('purple')
                                            ->placeholder('Sin actividad económica'),

                                        \Filament\Infolists\Components\TextEntry::make('business.entrepreneurshipStage.name')
                                            ->label('Estado actual del negocio')
                                            ->icon('heroicon-o-chart-bar-square')
                                            ->iconColor('success')
                                            ->placeholder('Sin etapa definida'),

                                        \Filament\Infolists\Components\TextEntry::make('created_at')
                                            ->label('Fecha de registro en el proyecto')
                                            ->icon('heroicon-o-calendar')
                                            ->iconColor('warning')
                                            ->date('d/m/Y')
                                            ->placeholder('Sin fecha')
                                            ->helperText(fn ($record) => $record->project?->name ?? 'Sin proyecto asignado'),
                                    ]),

                                \Filament\Infolists\Components\TextEntry::make('business.description')
                                    ->label('Descripción del emprendimiento')
                                    ->placeholder('Sin descripción')
                                    ->columnSpanFull(),
                            ]),

                        \Filament\Infolists\Components\Section::make('Mis Visitas')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->iconColor('warning')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('visits_count')
                                    ->label('')
                                    ->state(fn ($record) => $record->visits()->count() . ' visitas')
                                    ->badge()
                                    ->color('warning'),

                                \Filament\Infolists\Components\RepeatableEntry::make('visits')
                                    ->label('')
                                    ->schema([
                                        \Filament\Infolists\Components\Grid::make(2)
                                            ->schema([
                                                \Filament\Infolists\Components\TextEntry::make('visit_date')
                                                    ->label('Fecha y Hora')
                                                    ->icon('heroicon-o-calendar')
                                                    ->iconColor('warning')
                                                    ->formatStateUsing(function ($state, $record) {
                                                        $date = \Carbon\Carbon::parse($record->visit_date)->format('d/m/Y');
                                                        $time = $record->visit_time ? \Carbon\Carbon::parse($record->visit_time)->format('h:i A') : '';
                                                        return $date . ($time ? "\n⏰ " . $time : '');
                                                    }),

                                                \Filament\Infolists\Components\TextEntry::make('visit_type')
                                                    ->label('Tipo de Visita')
                                                    ->icon('heroicon-o-phone')
                                                    ->iconColor('primary')
                                                    ->formatStateUsing(function ($state) {
                                                        $types = [
                                                            'diagnostico' => 'diagnóstico',
                                                            'caracterizacion' => 'caracterización',
                                                            'seguimiento' => 'seguimiento',
                                                            'asesoria' => 'asesoría',
                                                            'capacitacion' => 'capacitación',
                                                        ];
                                                        return $types[$state] ?? $state;
                                                    }),

                                                \Filament\Infolists\Components\TextEntry::make('manager.name')
                                                    ->label('Gestor Asignado')
                                                    ->icon('heroicon-o-user')
                                                    ->iconColor('success')
                                                    ->placeholder('Sin gestor'),

                                                \Filament\Infolists\Components\TextEntry::make('strengthened')
                                                    ->label('Estado')
                                                    ->icon('heroicon-o-check-circle')
                                                    ->iconColor('info')
                                                    ->badge()
                                                    ->color(fn ($state) => $state ? 'success' : 'warning')
                                                    ->formatStateUsing(function ($state, $record) {
                                                        $status = $state ? 'Confirmada' : 'Pendiente';
                                                        $city = $record->entrepreneur?->city?->name ?? '';
                                                        return $status . ($city ? "\n" . $city : '');
                                                    }),
                                            ]),
                                    ])
                                    ->contained(false)
                                    ->columnSpanFull(),
                            ])
                            ->collapsed()
                            ->collapsible(),

                        \Filament\Infolists\Components\Section::make('Mi Caracterización')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->iconColor('warning')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('characterizations.0.characterization_date')
                                    ->label('Fecha')
                                    ->formatStateUsing(function ($state, $record) {
                                        $char = $record->characterizations()->first();
                                        if (!$char) return 'Sin caracterización';

                                        $date = $char->characterization_date ? \Carbon\Carbon::parse($char->characterization_date)->format('d/m/Y') : $char->created_at->format('d/m/Y');
                                        $manager = $char->manager?->name ?? 'Sin gestor';
                                        return $date . ' • Gestor: ' . $manager;
                                    })
                                    ->columnSpanFull(),

                                \Filament\Infolists\Components\Grid::make(2)
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('characterizations.0.economicActivity.name')
                                            ->label('Actividad Económica')
                                            ->icon('heroicon-o-briefcase')
                                            ->iconColor('primary')
                                            ->placeholder('Sin actividad económica')
                                            ->helperText('Sector secundario'),

                                        \Filament\Infolists\Components\TextEntry::make('characterizations.0.employees_generated')
                                            ->label('Empleos Generados')
                                            ->icon('heroicon-o-users')
                                            ->iconColor('success')
                                            ->formatStateUsing(function ($state) {
                                                $options = [
                                                    'up_to_2' => '3 a 4 empleados',
                                                    '3_to_4' => '3 a 4 empleados',
                                                    'more_than_5' => 'Más de 5 empleados',
                                                ];
                                                return $options[$state] ?? 'Sin información';
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('characterizations.0.business_type')
                                            ->label('Tipo de Negocio')
                                            ->icon('heroicon-o-building-office')
                                            ->iconColor('warning')
                                            ->formatStateUsing(function ($state) {
                                                return $state === 'individual' ? 'Individual' : 'Asociativo';
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('characterizations.0.promotion_strategies')
                                            ->label('Estrategias de Promoción')
                                            ->icon('heroicon-o-megaphone')
                                            ->iconColor('info')
                                            ->formatStateUsing(function ($state, $record) {
                                                $char = $record->characterizations()->first();
                                                if (!$char || !$char->promotion_strategies) return 'Sin estrategias';

                                                $strategies = $char->promotion_strategies;
                                                if (!is_array($strategies)) {
                                                    $strategies = json_decode($strategies, true);
                                                }

                                                if (!$strategies || empty($strategies)) return 'Sin estrategias';

                                                $labels = [
                                                    'word_of_mouth' => 'Voz a voz',
                                                    'whatsapp' => 'WhatsApp',
                                                    'facebook' => 'Facebook',
                                                    'instagram' => 'Instagram',
                                                ];

                                                $names = array_map(fn ($s) => $labels[$s] ?? $s, $strategies);
                                                return implode('  ,  ', $names);
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('characterizations.0.business_age')
                                            ->label('Antigüedad del Negocio')
                                            ->icon('heroicon-o-calendar-days')
                                            ->iconColor('purple')
                                            ->formatStateUsing(function ($state) {
                                                $ages = [
                                                    'over_6_months' => 'Más de 6 meses',
                                                    'over_12_months' => 'Más de 12 meses',
                                                    'over_24_months' => 'Más de 24 meses',
                                                ];
                                                return $ages[$state] ?? 'Sin información';
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('characterizations.0.average_monthly_sales')
                                            ->label('Ventas Mensuales Promedio')
                                            ->icon('heroicon-o-currency-dollar')
                                            ->iconColor('success')
                                            ->formatStateUsing(function ($state) {
                                                $ranges = [
                                                    'lt_500000' => '$2,001,000 — $5,000,000',
                                                    '500k_1m' => '$501,000 — $1,000,000',
                                                    '1m_2m' => '$1,001,000 — $2,000,000',
                                                    '2m_5m' => '$2,001,000 — $5,000,000',
                                                    'gt_5m' => 'Más de $5,001,000',
                                                ];
                                                return $ranges[$state] ?? 'Sin información';
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('characterizations.0.clients')
                                            ->label('Población Objetivo')
                                            ->icon('heroicon-o-user-group')
                                            ->iconColor('danger')
                                            ->formatStateUsing(function ($state, $record) {
                                                $char = $record->characterizations()->first();
                                                if (!$char || !$char->clients) return 'No Aplica';

                                                $clients = $char->clients;
                                                if (!is_array($clients)) {
                                                    $clients = json_decode($clients, true);
                                                }

                                                if (!$clients || empty($clients)) return 'No Aplica';

                                                $labels = [
                                                    'community' => 'Comunidad',
                                                    'public_entities' => 'Entidades públicas',
                                                    'private_entities' => 'Entidades privadas',
                                                    'schools' => 'Escuelas',
                                                    'hospitals' => 'Hospitales',
                                                ];

                                                $names = array_map(fn ($c) => $labels[$c] ?? $c, $clients);
                                                return implode('  ,  ', $names);
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('characterizations.0.has_accounting_records')
                                            ->label('Documentación')
                                            ->icon('heroicon-o-document-text')
                                            ->iconColor('gray')
                                            ->formatStateUsing(function ($state, $record) {
                                                $char = $record->characterizations()->first();
                                                if (!$char) return 'Sin información';

                                                $accounting = $char->has_accounting_records ? '✓ Con registros contables' : '';
                                                $commercial = $char->has_commercial_registration ? '✓ Registro comercial' : '';

                                                $docs = array_filter([$accounting, $commercial]);
                                                return $docs ? implode("\n", $docs) : 'Sin información';
                                            }),
                                    ]),
                            ])
                            ->collapsed()
                            ->collapsible(),

                        \Filament\Infolists\Components\Section::make('Documentos y Evidencias')
                            ->icon('heroicon-o-folder-open')
                            ->iconColor('warning')
                            ->schema([
                                // CARACTERIZACIÓN
                                \Filament\Infolists\Components\TextEntry::make('char_label')
                                    ->label('')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary')
                                    ->state('Caracterización:')
                                    ->visible(function ($record) {
                                        $char = $record->characterizations()->first();
                                        return $char && (
                                            $char->commerce_evidence_path ||
                                            $char->population_evidence_path ||
                                            $char->photo_evidence_path
                                        );
                                    })
                                    ->columnSpanFull(),

                                \Filament\Infolists\Components\Grid::make(3)
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('char_commerce')
                                            ->label('Evidencia de Comercio')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $char = $record->characterizations()->first();
                                                if (!$char || !$char->commerce_evidence_path) {
                                                    return 'Sin evidencia';
                                                }
                                                return self::renderFileButtons($char->commerce_evidence_path);
                                            })
                                            ->visible(function ($record) {
                                                $char = $record->characterizations()->first();
                                                return $char && $char->commerce_evidence_path;
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('char_population')
                                            ->label('Evidencia de Población')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $char = $record->characterizations()->first();
                                                if (!$char || !$char->population_evidence_path) {
                                                    return 'Sin evidencia';
                                                }
                                                return self::renderFileButtons($char->population_evidence_path);
                                            })
                                            ->visible(function ($record) {
                                                $char = $record->characterizations()->first();
                                                return $char && $char->population_evidence_path;
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('char_photo')
                                            ->label('Evidencia Fotográfica')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $char = $record->characterizations()->first();
                                                if (!$char || !$char->photo_evidence_path) {
                                                    return 'Sin evidencia';
                                                }
                                                return self::renderFileButtons($char->photo_evidence_path);
                                            })
                                            ->visible(function ($record) {
                                                $char = $record->characterizations()->first();
                                                return $char && $char->photo_evidence_path;
                                            }),
                                    ])
                                    ->visible(function ($record) {
                                        $char = $record->characterizations()->first();
                                        return $char && (
                                            $char->commerce_evidence_path ||
                                            $char->population_evidence_path ||
                                            $char->photo_evidence_path
                                        );
                                    }),

                                // PLAN DE NEGOCIO
                                \Filament\Infolists\Components\TextEntry::make('bp_label')
                                    ->label('')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary')
                                    ->state('Plan de Negocio:')
                                    ->visible(function ($record) {
                                        $bp = $record->businessPlan;
                                        return $bp && (
                                            $bp->business_plan_path ||
                                            $bp->acquisition_matrix_path ||
                                            $bp->business_model_path ||
                                            $bp->logo_path ||
                                            $bp->fire_pitch_video_url ||
                                            $bp->production_cycle_video_url
                                        );
                                    })
                                    ->columnSpanFull(),

                                \Filament\Infolists\Components\Grid::make(3)
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('bp_file')
                                            ->label('Plan de Negocio')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $bp = $record->businessPlan;
                                                if (!$bp || !$bp->business_plan_path) {
                                                    return null;
                                                }
                                                return self::renderFileButtons([$bp->business_plan_path], 'Ver plan');
                                            })
                                            ->visible(function ($record) {
                                                $bp = $record->businessPlan;
                                                return $bp && $bp->business_plan_path;
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('bp_matrix')
                                            ->label('Matriz de Adquisición')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $bp = $record->businessPlan;
                                                if (!$bp || !$bp->acquisition_matrix_path) {
                                                    return null;
                                                }
                                                return self::renderFileButtons([$bp->acquisition_matrix_path], 'Ver matriz');
                                            })
                                            ->visible(function ($record) {
                                                $bp = $record->businessPlan;
                                                return $bp && $bp->acquisition_matrix_path;
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('bp_model')
                                            ->label('Modelo de Negocio')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $bp = $record->businessPlan;
                                                if (!$bp || !$bp->business_model_path) {
                                                    return null;
                                                }
                                                return self::renderFileButtons([$bp->business_model_path], 'Ver modelo');
                                            })
                                            ->visible(function ($record) {
                                                $bp = $record->businessPlan;
                                                return $bp && $bp->business_model_path;
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('bp_logo')
                                            ->label('Logo')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $bp = $record->businessPlan;
                                                if (!$bp || !$bp->logo_path) {
                                                    return null;
                                                }
                                                return self::renderFileButtons([$bp->logo_path], 'Ver logo');
                                            })
                                            ->visible(function ($record) {
                                                $bp = $record->businessPlan;
                                                return $bp && $bp->logo_path;
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('bp_fire_pitch')
                                            ->label('Fire Pitch Video')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $bp = $record->businessPlan;
                                                if (!$bp || !$bp->fire_pitch_video_url) {
                                                    return null;
                                                }
                                                $html = '<a href="' . $bp->fire_pitch_video_url . '" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition">';
                                                $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                                $html .= '<span>Ver video</span>';
                                                $html .= '</a>';
                                                return $html;
                                            })
                                            ->visible(function ($record) {
                                                $bp = $record->businessPlan;
                                                return $bp && $bp->fire_pitch_video_url;
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('bp_production_cycle')
                                            ->label('Ciclo de Producción Video')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $bp = $record->businessPlan;
                                                if (!$bp || !$bp->production_cycle_video_url) {
                                                    return null;
                                                }
                                                $html = '<a href="' . $bp->production_cycle_video_url . '" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 transition">';
                                                $html .= '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                                $html .= '<span>Ver video</span>';
                                                $html .= '</a>';
                                                return $html;
                                            })
                                            ->visible(function ($record) {
                                                $bp = $record->businessPlan;
                                                return $bp && $bp->production_cycle_video_url;
                                            }),
                                    ])
                                    ->visible(function ($record) {
                                        $bp = $record->businessPlan;
                                        return $bp && (
                                            $bp->business_plan_path ||
                                            $bp->acquisition_matrix_path ||
                                            $bp->business_model_path ||
                                            $bp->logo_path ||
                                            $bp->fire_pitch_video_url ||
                                            $bp->production_cycle_video_url
                                        );
                                    }),

                                // FERIAS
                                \Filament\Infolists\Components\TextEntry::make('fair_label')
                                    ->label('')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary')
                                    ->state('Ferias:')
                                    ->visible(function ($record) {
                                        return \App\Models\FairEvaluation::where('entrepreneur_id', $record->id)
                                            ->whereNotNull('participation_photo_path')
                                            ->exists();
                                    })
                                    ->columnSpanFull(),

                                \Filament\Infolists\Components\RepeatableEntry::make('fairEvaluations')
                                    ->label('')
                                    ->getStateUsing(function ($record) {
                                        return $record->fairEvaluations()
                                            ->whereNotNull('participation_photo_path')
                                            ->with('fair')
                                            ->get();
                                    })
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('fair.name')
                                            ->label('Feria')
                                            ->weight('semibold'),

                                        \Filament\Infolists\Components\TextEntry::make('fair_photo')
                                            ->label('Foto de Participación')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                if (!$record->participation_photo_path) {
                                                    return null;
                                                }
                                                return self::renderFileButtons([$record->participation_photo_path], 'Ver foto');
                                            })
                                            ->visible(fn ($record) => $record->participation_photo_path),
                                    ])
                                    ->visible(function ($record) {
                                        return \App\Models\FairEvaluation::where('entrepreneur_id', $record->id)
                                            ->whereNotNull('participation_photo_path')
                                            ->exists();
                                    })
                                    ->columnSpanFull(),

                                // PQRFS
                                \Filament\Infolists\Components\TextEntry::make('pqrf_label')
                                    ->label('')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->color('primary')
                                    ->state('PQRFs:')
                                    ->visible(function ($record) {
                                        return $record->pqrfs()
                                            ->where(function ($q) {
                                                $q->whereNotNull('evidence_files')
                                                    ->orWhereNotNull('response_files');
                                            })
                                            ->exists();
                                    })
                                    ->columnSpanFull(),

                                \Filament\Infolists\Components\RepeatableEntry::make('pqrfs')
                                    ->label('')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('type')
                                            ->label('Tipo')
                                            ->weight('semibold')
                                            ->formatStateUsing(fn ($state) => ucfirst($state)),

                                        \Filament\Infolists\Components\Grid::make(2)
                                            ->schema([
                                                \Filament\Infolists\Components\TextEntry::make('pqrf_evidence')
                                                    ->label('Evidencias')
                                                    ->html()
                                                    ->formatStateUsing(function ($state, $record) {
                                                        if (!$record->evidence_files) {
                                                            return null;
                                                        }
                                                        return self::renderFileButtons($record->evidence_files, 'Ver evidencia');
                                                    })
                                                    ->visible(fn ($record) => $record->evidence_files),

                                                \Filament\Infolists\Components\TextEntry::make('pqrf_response')
                                                    ->label('Archivos de Respuesta')
                                                    ->html()
                                                    ->formatStateUsing(function ($state, $record) {
                                                        if (!$record->response_files) {
                                                            return null;
                                                        }
                                                        return self::renderFileButtons($record->response_files, 'Ver respuesta');
                                                    })
                                                    ->visible(fn ($record) => $record->response_files),
                                            ]),
                                    ])
                                    ->visible(function ($record) {
                                        return $record->pqrfs()
                                            ->where(function ($q) {
                                                $q->whereNotNull('evidence_files')
                                                    ->orWhereNotNull('response_files');
                                            })
                                            ->exists();
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->visible(function ($record) {
                                $char = $record->characterizations()->first();
                                $hasChar = $char && (
                                    $char->commerce_evidence_path ||
                                    $char->population_evidence_path ||
                                    $char->photo_evidence_path
                                );

                                $bp = $record->businessPlan;
                                $hasBP = $bp && (
                                    $bp->business_plan_path ||
                                    $bp->acquisition_matrix_path ||
                                    $bp->business_model_path ||
                                    $bp->logo_path ||
                                    $bp->fire_pitch_video_url ||
                                    $bp->production_cycle_video_url
                                );

                                $hasFairs = \App\Models\FairEvaluation::where('entrepreneur_id', $record->id)
                                    ->whereNotNull('participation_photo_path')
                                    ->exists();

                                $hasPQRFs = $record->pqrfs()
                                    ->where(function ($q) {
                                        $q->whereNotNull('evidence_files')
                                            ->orWhereNotNull('response_files');
                                    })
                                    ->exists();

                                return $hasChar || $hasBP || $hasFairs || $hasPQRFs;
                            })
                            ->collapsed()
                            ->collapsible(),
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntrepreneurPaths::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Entrepreneur::where('status', true)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
