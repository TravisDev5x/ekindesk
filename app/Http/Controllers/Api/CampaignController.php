<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ManagesOperatorCatalog;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    use ManagesOperatorCatalog;

    protected function catalogModelClass(): string
    {
        return Campaign::class;
    }

    public function index()
    {
        return $this->scopedCatalogQuery()->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'campaigns'),
            'is_active' => 'boolean',
        ]);

        $campaign = Campaign::create(array_merge([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ], $scope->operatorAttributesForCreate($user)));

        return response()->json($campaign, 201);
    }

    public function update(Request $request, Campaign $campaign)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $campaign);
        $scope = $this->catalogScope();
        $user = Auth::user();
        $data = $request->validate([
            'name' => $scope->uniqueNameRule($user, 'campaigns', $campaign->id),
            'is_active' => 'boolean',
        ]);

        $campaign->update([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? $campaign->is_active,
        ]);

        return response()->json($campaign);
    }

    public function destroy(Campaign $campaign)
    {
        $this->catalogScope()->authorizeRow(Auth::user(), $campaign);
        $campaign->delete();

        return response()->noContent();
    }
}
