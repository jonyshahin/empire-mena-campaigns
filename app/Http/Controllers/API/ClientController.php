<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
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
                'industry_ids' => 'nullable|array',
                'industry_ids.*' => 'nullable|integer|exists:industries,id',
                'brand_ids' => 'nullable|array',
                'brand_ids.*' => 'nullable|integer|exists:competitor_brands,id',
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
            ]));

            if ($request->hasFile('logo')) {
                $client->addMedia($request->file('logo'))->toMediaCollection('logo');
            }

            if ($request->hasFile('cover_image')) {
                $client->addMedia($request->file('cover_image'))->toMediaCollection('cover_image');
            }

            // check if industry_ids is an array
            if (is_array($request->industry_ids)) {
                $client->industries()->sync($request->industry_ids);
            }

            if (is_array($request->brand_ids)) {
                $client->brands()->sync($request->brand_ids);
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
                'industry_ids' => 'nullable|array',
                'industry_ids.*' => 'nullable|integer|exists:industries,id',
                'brand_ids' => 'nullable|array',
                'brand_ids.*' => 'nullable|integer|exists:competitor_brands,id',
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
            ]));

            if ($request->hasFile('logo')) {
                $client->clearMediaCollection('logo');
                $client->addMedia($request->file('logo'))->toMediaCollection('logo');
            }

            if ($request->hasFile('cover_image')) {
                $client->clearMediaCollection('cover_image');
                $client->addMedia($request->file('cover_image'))->toMediaCollection('cover_image');
            }

            if (is_array($request->industry_ids)) {
                $client->industries()->sync($request->industry_ids);
            }

            if (is_array($request->brand_ids)) {
                $client->industries()->sync($request->brand_ids);
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

            $client->industries()->detach();
            $client->brands()->detach();
            $client->clearMediaCollection('logo');
            $client->clearMediaCollection('cover_image');
            $client->delete();

            return custom_success(204, 'Company deleted successfully', null);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function get_clients(Request $request)
    {
        $clients = Client::where('id', $request->client_id)->with('companyUsers')->get();
        return custom_success(200, 'Clients', $clients);
    }

    public function store_client(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'company_id' => 'required|integer',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);
        $role = Role::findByName('client');
        $user->assignRole($role);

        $company_user = CompanyUser::create([
            'user_id' => $user->id,
            'client_id' => $validated['company_id'],
        ]);

        return custom_success(200, 'Client created successfully!', $user);
    }

    public function show_client(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_id' => 'required|integer',
            ]);

            $user = User::find($request->client_id);
            if (!$user) {
                return custom_error(404, 'Client not found');
            }

            return custom_success(200, 'Client', $user);
        } catch (\Throwable $th) {
            return custom_error(500, 'Something went wrong');
        }
    }

    public function update_client(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $request->client_id,
                'password' => 'nullable|string|min:8|confirmed',
            ]);

            $user = User::query()->where('id', $request->client_id)->role('client')->first();
            if (!$user) {
                return custom_error(404, 'Client not found');
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if ($validated['password']) {
                $user->password = bcrypt($validated['password']);
            }

            $user->save();

            return custom_success(200, 'Client updated successfully!', $user);
        } catch (\Throwable $th) {
            return custom_error(500, 'Something went wrong');
        }
    }
}
