<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSkinProfile;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class SkinAnalysisService
{
    // Dictionary: User Input => Database Tag
    private const CONCERN_KEYWORDS = [
        'bruntusan'     => 'Texture',
        'kasar'         => 'Texture',
        'tekstur'       => 'Texture',
        'mata panda'    => 'Dark Circles',
        'hitam di mata' => 'Dark Circles',
        'flek'          => 'Dark Spots',
        'noda hitam'    => 'Dark Spots',
        'bekas jerawat' => 'Acne Scars',
        'bopeng'        => 'Acne Scars',
        'merah'         => 'Redness',
        'gatal'         => 'Sensitive',
        'perih'         => 'Sensitive',
        'keriput'       => 'Anti Aging',
        'garis halus'   => 'Anti Aging',
        'kendur'        => 'Anti Aging',
        'pori'          => 'Pores',
        'lubang'        => 'Pores',
        'komedo'        => 'Blackheads'
    ];

    /**
     * Process User Answers and Save Profile
     */
    public function analyzeAndSaveProfile(User $user, array $data): UserSkinProfile
    {
        // 1. Calculate Skin Type
        $skinType = $this->determineSkinType($data['answers']);

        // 2. Map Keywords from Custom Text
        $mappedConcerns = $this->extractConcernsFromText($data['custom_concern'] ?? null);
        
        // 3. Merge with Checkbox Concerns & Remove Duplicates
        $finalConcerns = array_unique(array_merge(
            $data['concerns'] ?? [], 
            $mappedConcerns
        ));

        // 4. Save to Database
        return UserSkinProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'skin_type'     => $skinType,
                'skin_concerns' => array_values($finalConcerns),
                'answers_data'  => $data['answers']
            ]
        );
    }

    /**
     * Get Recommendation Products based on Profile
     */
    public function getRecommendations(User $user, int $limit = 4): Collection
    {
        $profile = UserSkinProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return new Collection();
        }

        $query = Product::with(['variants', 'category', 'brand']);

        // Filter 1: Skin Type (Mandatory Match)
        $query->whereJsonContains('suitability_tags', $profile->skin_type);

        // Filter 2: Skin Concerns (Optional Match)
        if (!empty($profile->skin_concerns)) {
            $query->orWhere(function($q) use ($profile) {
                foreach ($profile->skin_concerns as $concern) {
                    $q->orWhereJsonContains('suitability_tags', $concern);
                }
            });
        }

        return $query->inRandomOrder()->take($limit)->get();
    }

    /**
     * Helper: Logic to calculate skin type from answers
     */
    private function determineSkinType(array $answers): string
    {
        $counts = array_count_values($answers);
        $maxKey = !empty($counts) ? array_keys($counts, max($counts))[0] : 'B';

        return match ($maxKey) {
            'A' => 'Dry Skin',
            'B' => 'Normal Skin',
            'C' => 'Oily Skin',
            'D' => 'Combination Skin',
            default => 'Normal Skin',
        };
    }

    /**
     * Helper: Map text input to database tags
     */
    private function extractConcernsFromText(?string $text): array
    {
        if (empty($text)) return [];

        $text = strtolower($text);
        $foundTags = [];

        foreach (self::CONCERN_KEYWORDS as $keyword => $tag) {
            if (str_contains($text, $keyword)) {
                $foundTags[] = $tag;
            }
        }

        return $foundTags;
    }
}