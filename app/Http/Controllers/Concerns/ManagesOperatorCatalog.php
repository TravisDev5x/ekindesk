<?php

namespace App\Http\Controllers\Concerns;

use App\Services\OperatorCatalogScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait ManagesOperatorCatalog
{
    abstract protected function catalogModelClass(): string;

    protected function catalogScope(): OperatorCatalogScopeService
    {
        return app(OperatorCatalogScopeService::class);
    }

    protected function catalogTable(): string
    {
        return (new ($this->catalogModelClass()))->getTable();
    }

    protected function scopedCatalogQuery(): Builder
    {
        $class = $this->catalogModelClass();

        return $this->catalogScope()->apply($class::query(), Auth::user(), $this->catalogTable());
    }
}
