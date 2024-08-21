<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\QueryBuilder;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $per_page = isset($request->perPage) ? $request->perPage : 10;

            $clients = QueryBuilder::for(Client::class)
                ->allowedFilters([
                    'company_name',
                    'contact_person',
                    'website',
                    'phone',
                    'address',
                    'hq_map_name',
                    'hq_map_url',
                    'industry',
                ])
                ->defaultSort('-created_at')
                ->allowedSorts([
                    'company_name',
                    'contact_person',
                    'website',
                    'phone',
                    'address',
                    'hq_map_name',
                    'hq_map_url',
                    'industry',
                ]);

            if ($per_page == 0) {
                $clients = $clients->get();
            }
            if ($per_page > 0) {
                $clients = $clients->paginate($per_page);
            }

            return custom_success(200, 'Companies List', $clients);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /**
     * Store a newly created client in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_name' => 'required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'hq_map_name' => 'nullable|string|max:255',
                'hq_map_url' => 'nullable|url|max:255',
                'industry' => 'nullable|string|max:255',
                'logo' => 'nullable|image|max:2048',
                'cover_image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return custom_error(422, $validator->errors()->first());
            }

            $client = Client::create($request->only([
                'company_name',
                'contact_person',
                'website',
                'phone',
                'address',
                'hq_map_name',
                'hq_map_url',
                'industry',
            ]));

            if ($request->hasFile('logo')) {
                $client->addMedia($request->file('logo'))->toMediaCollection('logo');
            }

            if ($request->hasFile('cover_image')) {
                $client->addMedia($request->file('cover_image'))->toMediaCollection('cover_image');
            }

            return custom_success(201, 'Company created successfully', $client);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /**
     * Display the specified client.
     *
     *@param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:clients,id'
            ]);

            if ($validator->fails()) {
                return custom_error(422, $validator->errors()->first());
            }

            $client = Client::find($request->company_id);

            if (!$client) {
                return custom_error(404, 'Company not found');
            }

            return custom_success(200, 'Company details', $client);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /**
     * Update the specified client in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:clients,id',
                'company_name' => 'required|string|max:255',
                'contact_person' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'hq_map_name' => 'nullable|string|max:255',
                'hq_map_url' => 'nullable|url|max:255',
                'industry' => 'nullable|string|max:255',
                'logo' => 'nullable|image|max:2048',
                'cover_image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return custom_error(422, $validator->errors()->first());
            }

            $client = Client::find($request->company_id);

            if (!$client) {
                return custom_error(404, 'Company not found');
            }

            $client->update($request->only([
                'company_name',
                'contact_person',
                'website',
                'phone',
                'address',
                'hq_map_name',
                'hq_map_url',
                'industry',
            ]));

            if ($request->hasFile('logo')) {
                $client->clearMediaCollection('logo');
                $client->addMedia($request->file('logo'))->toMediaCollection('logo');
            }

            if ($request->hasFile('cover_image')) {
                $client->clearMediaCollection('cover_image');
                $client->addMedia($request->file('cover_image'))->toMediaCollection('cover_image');
            }

            return custom_success(200, 'Company updated successfully', $client);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    /**
     * Remove the specified client from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:clients,id'
            ]);

            if ($validator->fails()) {
                return custom_error(422, $validator->errors()->first());
            }

            $client = Client::find($request->company_id);

            if (!$client) {
                return custom_error(404, 'Company not found');
            }

            $client->clearMediaCollection('logo');
            $client->clearMediaCollection('cover_image');
            $client->delete();

            return custom_success(204, 'Company deleted successfully', null);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
