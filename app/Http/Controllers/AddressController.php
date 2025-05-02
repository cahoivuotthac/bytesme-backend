<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Http;
use Log;

class AddressController extends Controller
{
	protected function name_to_slug(string $name): string
	{
		// Remove common keywords from the name
		$replacements = [
			'/\bcity\b/i' => '',
			'/\bdistrict\b/i' => '',
			'/\bward\b/i' => '',
			'/\bprovince\b/i' => '',
			'/\btown\b/i' => ''
		];

		$name = preg_replace(array_keys($replacements), array_values($replacements), $name);
		$name = trim($name);
		// Convert the name to lowercase and replace spaces with hyphens
		$slug = strtolower(trim($name));
		$slug = preg_replace('/\s+/', '-', $slug);
		$slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
		Log::debug("Converted Slug: " . $slug);
		return $slug;
	}

	protected function address_items_from_slug(string $urbanSlug, string $suburbSlug, string $quarterSlug)
	{
		// Read the JSON file from the public directory
		$urbanJsonPath = public_path('constants/vietnam-address/tinh-tp.json');

		// Check if the file exists
		if (!file_exists($urbanJsonPath)) {
			return response()->json([
				'success' => false,
				'message' => 'Province & City list not found'
			], 500);
		}

		// Read and decode the JSON file
		$urbans = json_decode(file_get_contents($urbanJsonPath), associative: true);

		// Goal to is find these codes in the JSON files
		$foundUrban = null;
		$foundSuburb = null;
		$foundQuarter = null;

		foreach ($urbans as $urban) {
			if ($urban['slug'] === $urbanSlug) {
				$foundUrban = $urban;
				break;
			}
		}
		// Log::debug("Found Urban: " . json_encode($foundUrban));

		$suburbJsonPath = public_path("constants/vietnam-address/quan-huyen/{$foundUrban['code']}.json");
		$sububrs = json_decode(file_get_contents($suburbJsonPath), associative: true);
		foreach ($sububrs as $suburb) {
			if (
				$suburb['slug'] === $suburbSlug ||
				(is_numeric($suburb['slug']) && is_numeric($suburbSlug) && (int) $suburb['slug'] === (int) $suburbSlug)
			) {
				$foundSuburb = $suburb;
				break;
			}
		}
		// Log::debug("Found Suburb: " . json_encode($foundSuburb));

		$quarterJsonPath = public_path("constants/vietnam-address/xa-phuong/{$foundSuburb['code']}.json");
		$quarters = json_decode(file_get_contents($quarterJsonPath), associative: true);
		foreach ($quarters as $quarter) {
			if (
				$quarter['slug'] === $quarterSlug ||
				(is_numeric($quarter['slug']) && is_numeric($quarterSlug) && (int) $quarter['slug'] === (int) $quarterSlug)
			) {
				$foundQuarter = $quarter;
				break;
			}
		}
		// Log::debug("Found Quarter: " . json_encode($foundQuarter));

		return [
			'urban' => $foundUrban,
			'suburb' => $foundSuburb,
			'quarter' => $foundQuarter,
		];
	}

	public function reverse_geocode(Request $request)
	{
		$lat = $request->query('lat');
		$lon = $request->query('lon');

		// Validate the input
		if (empty($lat) || empty($lon)) {
			return response()->json(['error' => 'Latitude and Longitude are required'], 400);
		}

		try {
			// Call the Reverse Geocode API
			$apiKey = env('GEOMAP_API_KEY');
			if (!$apiKey) {
				return response()->json(['message' => 'missing server-side configuration'], 500);
			}
			$url = "https://geocode.maps.co/reverse?lat={$lat}&lon={$lon}&api_key={$apiKey}";
			$response = HTTP::get($url);

			if ($response->ok()) {
				$urbanName = $response['address']['city'] ?? null;
				$suburbName = $response['address']['suburb'] ?? null;
				$quarterName = $response['address']['quarter'] ?? null;

				// Get addtional address items from the slug
				['urban' => $urban, 'suburb' => $suburb, 'quarter' => $quarter] = $this->address_items_from_slug(
					$this->name_to_slug($urbanName),
					$this->name_to_slug($suburbName),
					$this->name_to_slug($quarterName)
				);

				$data = json_decode($response->body(), true);
				return response()->json([
					'urban' => $urban,
					'suburb' => $suburb,
					'quarter' => $quarter,
					'road' => $data['address']['road'] ?? null,
					'fullAddress' => $data['display_name'] ?? null,
				]);
			} else {
				return response()->json(['error' => 'Unable to retrieve address from geocode'], 500);
			}
		} catch (\Exception $e) {
			Log::error("Reverse geocoding error: " . $e->getMessage());
			return response()->json(['error' => 'An error occurred'], 500);
		}
	}
}
