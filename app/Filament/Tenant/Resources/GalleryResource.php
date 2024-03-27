<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\GalleryResource\Pages;
use App\Models\Tenants\UploadedFile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GalleryResource extends Resource
{
    protected static ?string $model = UploadedFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        FileUpload::make('name')
                            ->label('File')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->maxWidth('1/2')
                            ->panelAspectRatio('1:1')
                            ->storeFileNamesIn('original_name')
                            ->imageEditorMode(2)
                            ->previewable(true)
                            ->afterStateUpdated(function (Set $set, array $state) {
                                /** @var TemporaryUploadedFile $file */
                                foreach ($state as $file) {
                                    $name = $file->getClientOriginalName();
                                    $set('original_name', $name);
                                    $set('mime_type', $file->getMimeType());
                                    $set('extension', $file->getClientOriginalExtension());
                                    $set('size', $file->getSize());
                                }
                            })
                            ->multiple()
                            ->disk('tmp'),
                        Grid::make()->schema([
                            TextInput::make('original_name'),
                            TextInput::make('mime_type')
                                ->readOnly(),
                            TextInput::make('extension')
                                ->readOnly(),
                            TextInput::make('size')
                                ->suffix('kib')
                                ->readOnly(),
                            TextInput::make('disk')
                                ->hidden()
                                ->readOnly()
                                ->default('tmp'),
                        ])
                            ->columnSpan(2)
                            ->columns(1),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    ImageColumn::make('url')
                        ->height('200px')
                        ->width('full')
                        ->alignCenter(),
                    TextColumn::make('original_name')
                        ->alignCenter()
                        ->searchable(),
                ]),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 5,
            ])
            ->filters([
                //
            ])
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }
}
