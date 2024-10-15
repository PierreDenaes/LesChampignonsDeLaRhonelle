<?php

namespace App\Service;

use App\Entity\Rating;
use App\Entity\Recipe;
use App\Entity\Profile;
use App\Repository\RatingRepository;

class RatingService
{
    private RatingRepository $ratingRepository;

    public function __construct(RatingRepository $ratingRepository)
    {
        $this->ratingRepository = $ratingRepository;
    }

    public function getRatingData(Recipe $recipe, ?Profile $userProfile): array
    {
        $ratings = $this->ratingRepository->findBy(['recipe' => $recipe]);
        $ratingCount = count($ratings);

        $averageRating = $ratingCount > 0
            ? round(array_sum(array_map(fn(Rating $r) => $r->getScore(), $ratings)) / $ratingCount * 2) / 2
            : null;

        $existingRating = $userProfile
            ? $this->ratingRepository->findOneBy(['recipe' => $recipe, 'profile' => $userProfile])
            : null;

        return [
            'averageRating' => $averageRating,
            'ratingCount' => $ratingCount,
            'existingRating' => $existingRating,
        ];
    }
}