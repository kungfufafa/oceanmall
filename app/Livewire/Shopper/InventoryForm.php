<?php

declare(strict_types=1);

namespace App\Livewire\Shopper;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Shopper\Components\Section;
use Shopper\Components\Separator;
use Shopper\Livewire\Components\Settings\Locations\InventoryForm as ShopperInventoryForm;

final class InventoryForm extends ShopperInventoryForm
{
    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        return $schema->components([
            ...$schema->getComponents(),
            Separator::make(),
            Section::make(__('shopper::pages/settings/global.location.rajaongkir_origin'))
                ->aside()
                ->compact()
                ->description(__('shopper::pages/settings/global.location.rajaongkir_origin_summary'))
                ->extraAttributes(['class' => 'sh-section-aside'])
                ->schema([
                    TextInput::make('rajaongkir_origin_id')
                        ->label(__('shopper::pages/settings/global.location.rajaongkir_origin_id'))
                        ->helperText(__('shopper::pages/settings/global.location.rajaongkir_origin_helper'))
                        ->numeric()
                        ->minValue(1),
                ]),
        ]);
    }
}
