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
            $per_page = $request->perPage ?? 10;

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
                ])
                ->paginate($per_page);

            return custom_success(200, 'Clients List', $clients);
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
                return $this->custom_error(422, $validator->errors()->first());
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

            return $this->custom_success(201, 'Client created successfully', $client);
        } catch (\Throwable $th) {
            return $this->custom_error(500, $th->getMessage());
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
                'client_id' => 'required|integer|exists:clients,id'
            ]);

            if ($validator->fails()) {
                return $this->custom_error(422, $validator->errors()->first());
            }

            $client = Client::find($request->client_id);

            if (!$client) {
                return $this->custom_error(404, 'Client not found');
            }

            return $this->custom_success(200, 'Client details', $client);
        } catch (\Throwable $th) {
            return $this->custom_error(500, $th->getMessage());
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
                'client_id' => 'required|integer|exists:clients,id',
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
                return $this->custom_error(422, $validator->errors()->first());
            }

            $client = Client::find($request->client_id);

            if (!$client) {
                return $this->custom_error(404, 'Client not found');
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

            return $this->custom_success(200, 'Client updated successfully', $client);
        } catch (\Throwable $th) {
            return $this->custom_error(500, $th->getMessage());
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
                'client_id' => 'required|integer|exists:clients,id'
            ]);

            if ($validator->fails()) {
                return $this->custom_error(422, $validator->errors()->first());
            }

            $client = Client::find($request->client_id);

            if (!$client) {
                return $this->custom_error(404, 'Client not found');
            }

            $client->clearMediaCollection('logo');
            $client->clearMediaCollection('cover_image');
            $client->delete();

            return $this->custom_success(204, 'Client deleted successfully', null);
        } catch (\Throwable $th) {
            return $this->custom_error(500, $th->getMessage());
        }
    }
}
