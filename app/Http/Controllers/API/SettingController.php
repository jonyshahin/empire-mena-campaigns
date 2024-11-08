<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $settings = Setting::all()->pluck('value', 'key');

            return custom_success(200, 'Setting List', $settings);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function set(Request $request)
    {
        try {
            // Get all the request data
            $data = $request->all();

            // Loop through each key-value pair in the request
            foreach ($data as $key => $value) {
                // Use updateOrCreate to set each key-value pair in the settings table
                Setting::updateOrCreate(
                    ['key' => $key],   // Search by key
                    ['value' => $value] // Set the value
                );
            }

            return custom_success(200, 'Setting Updated Successfully', $data);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'key' => 'required|string',
                    'value' => 'nullable',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model = Setting::create([
                'key' => $request->key,
                'value' => $request->input('value'),
            ]);

            return custom_success(200, 'Setting Created Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $model = Setting::find($id);
            if (!$model) {
                return custom_error(404, 'Setting Not Found');
            }

            return custom_success(200, 'Setting Details', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $model = Setting::find($id);
            if (!$model) {
                return custom_error(404, 'Setting Not Found');
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'key' => 'nullable|string',
                    'value' => 'nullable',
                ]
            );

            if ($validator->fails()) {
                return validation_error($validator->messages()->all());
            }

            $model->key = $request->input('key', $model->key);
            $model->value = $request->input('value', $model->value);
            $model->save();

            return custom_success(200, 'Setting Updated Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $model = Setting::find($id);
            if (!$model) {
                return custom_error(404, 'Setting Not Found');
            }

            $model->delete();

            return custom_success(200, 'Setting Deleted Successfully', $model);
        } catch (\Throwable $th) {
            return custom_error(500, $th->getMessage());
        }
    }
}
