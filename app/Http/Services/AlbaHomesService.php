<?php

namespace App\Http\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;

class AlbaHomesService
{
    private $albaHomesUrl;
    public function __construct()
    {
        $this->albaHomesUrl = config('services.alba.api_url');
    }
    public function getAlbaHomesData()
    {
        try {
            // Faire la requête à l'API externe
            $response = Http::timeout(10)
                ->retry(3, 500)
                ->get($this->albaHomesUrl);

            // Vérifier si la requête a réussi
            // Vérifie si la requête a réussi (code 200-299)
            if ($response->successful()) {
                $data = $response->json(); // Retourne les données en tableau PHP (si JSON)

                return response()->json($data);
            }

            // En cas d'erreur
            return response()->json([
                'error' => 'Erreur API',
                'status' => $response->status(),
                'body' => $response->body()
            ], $response->status());
        } catch (ConnectionException $e) {
            // Gestion des erreurs de connexion
            return response()->json([
                'status' => 'error',
                'message' => 'Could not connect to external API',
                'error' => $e->getMessage(),
            ], 503);
        } catch (\Exception $e) {
            // Gestion des autres erreurs
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAlbaHomesDataWithParams($params) {}
}
