<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\AlbaHomesService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Annotations as OA;

class HomeController extends Controller
{
    private $albaHomesUrl;

    public function __construct()
    {
        $this->albaHomesUrl = config('services.alba.api_url');
    }

    /**
     * @OA\Get(
     *     path="/api/alba-homes",
     *     summary="Get Alba Homes data",
     *     tags={"Alba Homes"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function albaHomesAllData(AlbaHomesService $albaHomesService)
    {
        $jsonResponse = $albaHomesService->getAlbaHomesData();

        // Méthode 1 : récupérer le contenu sous forme de tableau
        $data = $jsonResponse->getData(true); // true = retourne un array, false = objet

        // Ensuite tu peux renvoyer ou modifier
        return response()->json($data);
    }

    /**
     * @OA\Get(
     *     path="/api/alba-homes",
     *     summary="Get Alba Homes data with params",
     *     tags={"Alba Homes"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */
    public function albaHomesDataWithParams(AlbaHomesService $albaHomesService, Request $request)
    {

        $params = '';

        $req = $request->all();

        $search = $req['search'];
        $tags = $req['tags'];
        $minPrice = $req['minPrice'];
        $maxPrice = $req['maxPrice'];
        $downPayment = $req['downPayment'];
        $communityName = $req['communityName'];
        $radius = $req['radius'];
        $propertyType = $req['propertyType'];
        $handOver = $req['handOver'];

        if ($search) {
            $params .= 'search=' . $search . '&';
        }

        if ($tags) {
            $params .= 'tags=' . $tags . '&';
        }

        if ($minPrice) {
            $params .= 'minPrice=' . $minPrice . '&';
        }

        if ($maxPrice) {
            $params .= 'maxPrice=' . $maxPrice . '&';
        }

        if ($downPayment) {
            $params .= 'downPayment=' . $downPayment . '&';
        }

        if ($communityName) {
            $params .= 'communityName=' . $communityName . '&';
        }

        if ($radius) {
            $params .= 'radius=' . $radius . '&';
        }

        if ($propertyType) {
            $params .= 'propertyType=' . $propertyType . '&';
        }

        if ($handOver) {
            $params .= 'handOver=' . $handOver . '&';
        }

        // foreach ($req as $key => $value) {
        //     $params .= $key . '=' . $value . '&';
        // }

        dd($params);
        $jsonResponse = $albaHomesService->getAlbaHomesDataWithParams($request->all());

        // Méthode 1 : récupérer le contenu sous forme de tableau
        $data = $jsonResponse->getData(true); // true = retourne un array, false = objet

        // Ensuite tu peux renvoyer ou modifier
        return response()->json($data);
    }
}
