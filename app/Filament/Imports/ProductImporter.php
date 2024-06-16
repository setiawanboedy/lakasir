<?php

namespace App\Filament\Imports;

use App\Models\Tenants\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    protected function beforeValidate(): void
    {
        dump($this->data);
    }

    protected function afterValidate(): void
    {
        dump($this->data);
    }

    protected function beforeFill(): void
    {
        dump($this->data);
    }

    protected function afterFill(): void
    {
        dump($this->data);
    }

    protected function beforeSave(): void
    {
        dump($this->data);
    }

    protected function beforeCreate(): void
    {
        dump($this->data);
    }

    protected function beforeUpdate(): void
    {
        dump($this->data);
    }

    protected function afterSave(): void
    {
        dump($this->data);
    }

    protected function afterCreate(): void
    {
        dump($this->data);
    }

    protected function afterUpdate(): void
    {
        dump($this->data);
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('category')
                ->rules(['required'])
                ->example('Category Name'),
            ImportColumn::make('name')
                ->rules(['required', 'string']),
            ImportColumn::make('sku')
                ->rules(['required', 'string', 'unique:products,sku'])
                ->example('SKU123/Kosongin'),
            ImportColumn::make('sku')
                ->rules(['required', 'string', 'unique:products,barcode'])
                ->example('11111/Kosongin'),
            ImportColumn::make('stock')
                ->rules(['required', 'numeric'])
                ->numeric(),
            ImportColumn::make('unit')
                ->rules(['required', 'string'])
                ->example('PCS')
                ->numeric(),
            ImportColumn::make('initial_price')
                ->rules(['required', 'numeric'])
                ->example('1000')
                ->numeric(decimalPlaces: 2),
            ImportColumn::make('selling_price')
                ->rules(['required', 'numeric'])
                ->example('1500')
                ->numeric(decimalPlaces: 2),
            ImportColumn::make('expired')
                ->rules(['required', 'numeric'])
                ->example(now()->format('Y-m-d')),
            ImportColumn::make('type')
                ->example('product/service')
                ->rules(['required', 'in:product,service']),
            ImportColumn::make('is_non_stock')
                ->rules(['required', 'boolean'])
                ->example('Yes/No')
                ->castStateUsing(function (string $state) {
                    return filter_var($state, FILTER_VALIDATE_BOOLEAN);
                }),
        ];
    }

    public function resolveRecord(): ?Product
    {
        dd($this->data);

        return Product::firstOrNew([
            // Update existing records, matching them by `$this->data['column_name']`
        ]);

        return new Product();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your product import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
