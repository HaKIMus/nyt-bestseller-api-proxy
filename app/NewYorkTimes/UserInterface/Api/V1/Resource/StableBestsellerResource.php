<?php

declare(strict_types=1);

namespace App\NewYorkTimes\UserInterface\Api\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class StableBestsellerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->getFieldValue('title'),
            'author' => $this->getFieldValue('author'),
            'isbn' => $this->getFieldValue('isbn'),
            'publisher' => $this->getFieldValue('publisher'),
            'description' => $this->getFieldValue('description'),
            'rank' => $this->getFieldValue('rank'),
            'rank_last_week' => $this->getFieldValue('rank_last_week'),
            'weeks_on_list' => $this->getFieldValue('weeks_on_list'),
            'ranks_history' => $this->getFieldValue('ranks_history'),
            'reviews' => $this->getFieldValue('reviews'),
        ];
    }

    private function getFieldValue(string $stableFieldName): mixed
    {
        $possibleFieldPaths = Config::get("nyt_field_mapping.{$stableFieldName}", [$stableFieldName]);

        foreach ($possibleFieldPaths as $fieldPath) {
            if (is_string($fieldPath)) {
                if (isset($this->resource[$fieldPath])) {
                    return $this->resource[$fieldPath];
                }
                continue;
            }

            if (is_array($fieldPath) && isset($fieldPath['path'])) {
                $value = $this->getNestedValue(
                    $fieldPath['path'],
                    $fieldPath['fallback'] ?? null
                );

                if ($value !== null && isset($fieldPath['transform']) && is_string($fieldPath['transform']) && method_exists($this, $fieldPath['transform'])) {
                    $value = $this->{$fieldPath['transform']}($value);
                }

                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function getNestedValue(string $path, mixed $default = null): mixed
    {
        return Arr::get($this->resource, $path, $default);
    }
}
