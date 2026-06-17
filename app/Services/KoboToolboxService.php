<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class KoboToolboxService
{
    private string $apiUrl;
    private string $token;
    private string $formUid;

    public function __construct()
    {
        $this->apiUrl  = rtrim(config('services.kobotoolbox.api_url', 'https://kf.kobotoolbox.org/api/v2'), '/');
        $this->token   = config('services.kobotoolbox.token')   ?? '';
        $this->formUid = config('services.kobotoolbox.form_uid') ?? '';

        if (empty($this->token) || empty($this->formUid)) {
            throw new \RuntimeException(
                'KoboToolbox non configuré : définissez KOBO_API_TOKEN et KOBO_FORM_UID dans votre fichier .env'
            );
        }
    }

    private function get(string $endpoint, array $params = []): mixed
    {
        $response = Http::withHeaders(['Authorization' => "Token {$this->token}"])
            ->timeout(60)
            ->retry(2, 3000)
            ->get("{$this->apiUrl}/{$endpoint}", $params);

        $response->throw();
        return $response->json();
    }

    // ── Définition du formulaire (labels des champs) ─────────────────────────

    public function getFormDefinition(): array
    {
        return Cache::remember('kobo.form_definition', now()->addHours(6), fn() =>
            $this->get("assets/{$this->formUid}/")
        );
    }

    // ── Soumissions (toutes, avec pagination automatique) ────────────────────

    public function getSubmissions(): array
    {
        return Cache::remember('kobo.submissions', now()->addMinutes(30), function () {
            $all   = [];
            $start = 0;
            $limit = 500;

            do {
                $data    = $this->get("assets/{$this->formUid}/data/", [
                    'format' => 'json',
                    'limit'  => $limit,
                    'start'  => $start,
                ]);
                $results = $data['results'] ?? [];
                $all     = array_merge($all, $results);
                $start  += $limit;
            } while (!empty($data['next']) && count($results) === $limit);

            return $all;
        });
    }

    // ── Mapping nom_champ → label lisible ────────────────────────────────────
    // Extrait les labels depuis le contenu du formulaire XLSForm.

    public function getFieldLabels(): array
    {
        return Cache::remember('kobo.field_labels', now()->addHours(6), function () {
            $definition = $this->getFormDefinition();
            $survey     = $definition['content']['survey'] ?? [];
            $labels     = [];

            foreach ($survey as $field) {
                $name = $field['$autoname'] ?? $field['name'] ?? null;
                if (!$name) continue;

                $label = $field['label'][0] ?? $field['label'] ?? $name;
                if (is_array($label)) {
                    $label = reset($label);
                }
                $labels[$name] = $label;
            }

            return $labels;
        });
    }

    // ── Cache ─────────────────────────────────────────────────────────────────

    public function clearCache(): void
    {
        Cache::forget('kobo.form_definition');
        Cache::forget('kobo.submissions');
        Cache::forget('kobo.field_labels');
    }
}
