<?php

use GuzzleHttp\Client;
use Illuminate\Http\Response;

function validation_error($message)
{
    return response()->json([
        "message" => $message[0],
        "code" => Response::HTTP_BAD_REQUEST,
        "data" => null,
        "response" => 'error'
    ], Response::HTTP_BAD_REQUEST);
}

function custom_error($code, $msg)
{
    return response()->json([
        'code' => $code,
        'response' => 'error',
        'message' => $msg,
        'data' => (object)(array())
    ], $code);
}

function custom_success($code, $msg, $data)
{
    return response()->json([
        'code' => $code,
        'response' => 'success',
        'message' => $msg,
        'data' => $data
    ], $code);
}

function users_search($fetch_users, $search)
{

    $fetch_users = $fetch_users->where('name', 'LIKE', '%' . $search . '%')
        ->orWhere('email', 'LIKE', '%' . $search . '%')
        ->orWhere('phone', 'LIKE', '%' . $search . '%')
        ->whereHas('company', function ($query) use ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        });

    return $fetch_users;
}

//whatsapp_login function
function whatsapp_message($phon_number, $otp)
{
    $fetch_data = [
        'code' => 1,
        'success' => true,
        'message' => '',
    ];

    try {
        $bearerToken = '226|zWJkljZaENPV2f7eKMKpmSTABeAGoS6WH0H9dDAa4c07cc8e';

        $client = new Client();

        $headers = [
            'Authorization' => 'Bearer ' . $bearerToken,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];

        $body = [
            'recipient' => $phon_number,
            'sender_id' => 'Empire Mena',
            'type'      => 'whatsapp',
            'message'   => $otp,
            'lang'      => 'en',
        ];

        $response = $client->request('POST', 'https://gateway.standingtech.com/api/v4/sms/send', [
            'headers' => $headers,
            'json'    => $body,
        ]);

        $data = $response->getBody()->getContents();
        $statusCode = $response->getStatusCode();

        if ($statusCode == 200) {
            $fetch_data['message'] = 'Message Sent Successfully';
            $fetch_data['data'] = $data;

            return $fetch_data;
        } else {
            $fetch_data['message'] = 'Invalid Number';
            $fetch_data['code'] = 0;
            $fetch_data['success'] = false;
            $fetch_data['data'] = 'Invalid Number';

            return $fetch_data;
        }
    } catch (\Throwable $th) {
        //throw $th;
        $fetch_data['message'] = $th->getMessage();
        $fetch_data['code'] = 0;
        $fetch_data['success'] = false;
        $fetch_data['data'] = "Invalid Number";
        return $fetch_data;
    }
}
