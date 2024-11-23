<?php

namespace App\Http\Controllers\Api\v1\Nyt;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\BestSellersRequest;
use Illuminate\Validation\ValidationException;

class BestSellersController extends Controller
{

    public function index(BestSellersRequest $request)
    {
        $validatedData = $request->validated();

        try{
            $best_sellers_url = config("services.new_york_times.url").'/svc/books/v3/lists/best-sellers/history.json';

            // Format ISBN parameter according to NYT API requirements
            if(isset($validatedData['isbn']) && count($validatedData['isbn'])>0){
                $isbnParam =  implode(';', $validatedData['isbn']) ;
                $validatedData['isbn'] =  $isbnParam ;
            }

            $response =  Http::withHeaders([
                    'Accept' => 'application/json',
                ])->get($best_sellers_url,[
                    'api-key' => config('services.new_york_times.key'),
                    ...$validatedData
                ]);

            // Check if the request was successful
            if ($response->successful()) {
                return $response->body();
            }

            // Log the error
            Log::error('NYT API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => json_encode($validatedData),
            ]);

            // Handle different error status codes
            $errorMessage = '';
            match ($response->status()) {
                429 => $errorMessage = 'Rate limit exceeded. Please try again later.',
                404 => $errorMessage = 'No results found.',
                default => $errorMessage ='Failed to fetch data',
            };

            return response()->json(['error' => $errorMessage], $response->status());
        }
        catch (\Exception $e) {
            Log::error('NYT API Request Failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'something went wrong'], Response::HTTP_BAD_REQUEST);
        }
    }

}
