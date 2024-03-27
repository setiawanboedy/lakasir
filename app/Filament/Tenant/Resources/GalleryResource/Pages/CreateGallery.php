<?php

namespace App\Filament\Tenant\Resources\GalleryResource\Pages;

use App\Filament\Tenant\Resources\GalleryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGallery extends CreateRecord
{
    protected static string $resource = GalleryResource::class;

    protected function getRedirectUrl(): string
    {
        return '/member/galleries';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $url = url('/tmp/'.$data['name']);
        $path = storage_path('app/tmp/'.$data['name']);
        $data = array_merge($data, ['url' => $url, 'path' => $path, 'disk' => 'tmp']);
        $record = parent::handleRecordCreation($data);

        return $record;
    }
}
