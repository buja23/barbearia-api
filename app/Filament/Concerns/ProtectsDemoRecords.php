<?php

namespace App\Filament\Concerns;

use App\Support\DemoAccess;
use Illuminate\Database\Eloquent\Model;

trait ProtectsDemoRecords
{
    public static function canDelete(Model $record): bool
    {
        return ! DemoAccess::protects($record) && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return ! DemoAccess::protects(filament()->getTenant()) && parent::canDeleteAny();
    }

    public static function canForceDelete(Model $record): bool
    {
        return ! DemoAccess::protects($record) && parent::canForceDelete($record);
    }

    public static function canForceDeleteAny(): bool
    {
        return ! DemoAccess::protects(filament()->getTenant()) && parent::canForceDeleteAny();
    }
}
