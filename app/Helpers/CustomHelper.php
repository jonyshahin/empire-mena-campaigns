<?php

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

function calculateCohesiveFOS($W, $L1, $L2, $P1, $P2, $SU, $FOS1req, $FOS2req, $T)
{
    $maxAppliedLoad = $W * $L1 * $P1;
    $areaOfAppliedLoad = ($W + 2 * $T) * ($L1 + 2 * $T);
    $bearingPressure = $maxAppliedLoad / $areaOfAppliedLoad;
    $sharedFactor = 1 + 0.2 * (($W + 2 * $T) / ($L1 + 2 * $T));
    $qUltimate = 5.14 * $SU * $sharedFactor;
    $FOS1 = $qUltimate / $bearingPressure;

    $maxAppliedLoad = $W * $L2 * $P2;
    $areaOfAppliedLoad = ($W + 2 * $T) * ($L2 + 2 * $T);
    $bearingPressure = $maxAppliedLoad / $areaOfAppliedLoad;
    $sharedFactor = 1 + 0.2 * (($W + 2 * $T) / ($L2 + 2 * $T));
    $qUltimate = 5.14 * $SU * $sharedFactor;
    $FOS2 = $qUltimate / $bearingPressure;

    if ($FOS1 >= $FOS1req && $FOS2 >= $FOS2req) {
        $data = [
            'FOS1' => round($FOS1, 2),
            'FOS2' => round($FOS2, 2),
            'T' => round($T * 1000, 2),
        ];
        return $data;
    } else {
        $T = $T + 0.01;
        return calculateCohesiveFOS($W, $L1, $L2, $P1, $P2, $SU, $FOS1req, $FOS2req, $T);
    }
}

function calculateGranularFOS($W, $L1, $L2, $P1, $P2, $FA, $UWS, $UWP, $FOS1req, $FOS2req, $T)
{
    $data['W'] = $W;
    $data['L1'] = $L1;
    $data['L2'] = $L2;
    $data['P1'] = $P1;
    $data['P2'] = $P2;
    $data['FA'] = $FA;
    $data['UWS'] = $UWS;
    $data['UWP'] = $UWP;
    $data['FOS1req'] = $FOS1req;
    $data['FOS2req'] = $FOS2req;

    $factor2 = exp(pi() * tan(deg2rad($FA))) * pow(tan(deg2rad(45 + $FA / 2.0)), 2);
    $factor1 = 1.5 * ($factor2 - 1.0) * tan(deg2rad($FA));

    $maxAppliedLoad = $W * $L1 * $P1;
    $areaOfAppliedLoad = ($W + 2.0 * $T) * ($L1 + 2.0 * $T);
    $bearingPressure = $maxAppliedLoad / $areaOfAppliedLoad;
    $factor3 = $W + 2.0 * $T;
    $factor4 = 1.0 - 0.3 * (((2.0 * $T) + $W) / ((2.0 * $T) + $L1));
    $P0 = $UWP * $T;
    $factor5 = 1.0 + $W / $L1 * tan(deg2rad($FA));
    $qUltimate = (0.5 * $factor3 * $factor1 * $factor4 * $UWS) + ($factor2 * $P0 * $factor5);
    $FOS1 = $qUltimate / $bearingPressure;

    $data['factor2'] = $factor2;
    $data['factor1'] = $factor1;
    $data['lc1maxAppliedLoad'] = $maxAppliedLoad;
    $data['lc1areaOfAppliedLoad'] = $areaOfAppliedLoad;
    $data['lc1bearingPressure'] = $bearingPressure;
    $data['lc1factor3'] = $factor3;
    $data['lc1factor4'] = $factor4;
    $data['lc1P0'] = $P0;
    $data['lc1factor5'] = $factor5;
    $data['lc1qUltimate'] = $qUltimate;

    $maxAppliedLoad = $W * $L2 * $P2;
    $areaOfAppliedLoad = ($W + 2 * $T) * ($L2 + 2 * $T);
    $bearingPressure = $maxAppliedLoad / $areaOfAppliedLoad;
    $factor3 = $W + 2.0 * $T;
    $factor4 = 1.0 - 0.3 * (((2.0 * $T) + $W) / ((2.0 * $T) + $L2));
    $P0 = $UWP * $T;
    $factor5 = 1.0 + $W / $L2 * tan(deg2rad($FA));
    $qUltimate = (0.5 * $factor3 * $factor1 * $factor4 * $UWS) + ($factor2 * $P0 * $factor5);
    $FOS2 = $qUltimate / $bearingPressure;

    $data['lc2maxAppliedLoad'] = $maxAppliedLoad;
    $data['lc2areaOfAppliedLoad'] = $areaOfAppliedLoad;
    $data['lc2bearingPressure'] = $bearingPressure;
    $data['lc2factor3'] = $factor3;
    $data['lc2factor4'] = $factor4;
    $data['lc2P0'] = $P0;
    $data['lc2factor5'] = $factor5;
    $data['lc2qUltimate'] = $qUltimate;

    if ($FOS1 >= $FOS1req && $FOS2 >= $FOS2req) {
        $data['FOS1'] = round($FOS1, 2);
        $data['FOS2'] = round($FOS2, 2);
        $data['T'] = round($T * 1000, 2);

        return $data;
    } else {
        $T = $T + 0.01;
        return calculateGranularFOS($W, $L1, $L2, $P1, $P2, $FA, $UWS, $UWP, $FOS1req, $FOS2req, $T);
    }
}

function pfsCalculateCohesiveFOS($W, $L, $P, $SU, $FOSreq, $T)
{
    $maxAppliedLoad = $W * $L * $P;
    $areaOfAppliedLoad = ($W + 2 * $T) * ($L + 2 * $T);
    $bearingPressure = $maxAppliedLoad / $areaOfAppliedLoad;
    $sharedFactor = 1 + 0.2 * (($W + 2 * $T) / ($L + 2 * $T));
    $qUltimate = 5.14 * $SU * $sharedFactor;
    $FOS = $qUltimate / $bearingPressure;

    if ($FOS >= $FOSreq) {
        $data = [
            'FOS' => round($FOS, 2),
            'T' => round($T * 1000, 2),
        ];
        return $data;
    } else {
        $T = $T + 0.01;
        return pfsCalculateCohesiveFOS($W, $L, $P, $SU, $FOSreq, $T);
    }
}

function pfsCalculateGranularFOS($W, $L, $P, $FA, $UWS, $UWP, $FOSreq, $T)
{
    $data['W'] = $W;
    $data['L'] = $L;
    $data['P'] = $P;
    $data['FA'] = $FA;
    $data['UWS'] = $UWS;
    $data['UWP'] = $UWP;
    $data['FOSreq'] = $FOSreq;

    $factor2 = exp(pi() * tan(deg2rad($FA))) * pow(tan(deg2rad(45 + $FA / 2.0)), 2);
    $factor1 = 1.5 * ($factor2 - 1.0) * tan(deg2rad($FA));

    $maxAppliedLoad = $W * $L * $P;
    $areaOfAppliedLoad = ($W + 2.0 * $T) * ($L + 2.0 * $T);
    $bearingPressure = $maxAppliedLoad / $areaOfAppliedLoad;
    $factor3 = $W + 2.0 * $T;
    $factor4 = 1.0 - 0.3 * (((2.0 * $T) + $W) / ((2.0 * $T) + $L));
    $P0 = $UWP * $T;
    $factor5 = 1.0 + $W / $L * tan(deg2rad($FA));
    $qUltimate = (0.5 * $factor3 * $factor1 * $factor4 * $UWS) + ($factor2 * $P0 * $factor5);
    $FOS = $qUltimate / $bearingPressure;

    $data['factor2'] = $factor2;
    $data['factor1'] = $factor1;
    $data['lcmaxAppliedLoad'] = $maxAppliedLoad;
    $data['lcareaOfAppliedLoad'] = $areaOfAppliedLoad;
    $data['lcbearingPressure'] = $bearingPressure;
    $data['lcfactor3'] = $factor3;
    $data['lcfactor4'] = $factor4;
    $data['lcP0'] = $P0;
    $data['lcfactor5'] = $factor5;
    $data['lcqUltimate'] = $qUltimate;

    if ($FOS >= $FOSreq) {
        $data['FOS'] = round($FOS, 2);
        $data['T'] = round($T * 1000, 2);

        return $data;
    } else {
        $T = $T + 0.01;
        return pfsCalculateGranularFOS($W, $L, $P, $FA, $UWS, $UWP, $FOSreq, $T);
    }
}

function calculateLayersThickness($T)
{
    if ($T > 800 && ($T / 2) % 2 == 1) {
        $G3 = 210;
        $layers['G3'] = $G3;
        $layers['G2'] = ($T - $G3) / 2;
        $layers['G1'] = ($T - $G3) / 2;
        $layers['T'] = $T;
    } elseif ($T > 800 && ($T / 2) % 2 == 0) {
        $G3 = 200;
        $layers['G3'] = $G3;
        $layers['G2'] = ($T - $G3) / 2;
        $layers['G1'] = ($T - $G3) / 2;
        $layers['T'] = $T;
    } elseif ($T >= 600 && $T <= 800) {
        $G2 = 400;
        $layers['G2'] = $G2;
        $layers['G3'] = $T - $G2;
        $layers['T'] = $T;
    } elseif ($T < 600 && $T > 400) {
        $G3 = 200;
        $layers['G3'] = $G3;
        $layers['G2'] = $T - $G3;
        $layers['T'] = $T;
    } elseif ($T <= 400 && $T > 350) {
        $G3 = $T;
        $layers['G3'] = $G3;
        $layers['T'] = $T;
    } elseif ($T <= 350 && $T > 300) {
        $G3 = 350;
        $layers['G3'] = $G3;
        $layers['T'] = $G3;
    } elseif ($T <= 300) {
        $G3 = 300;
        $layers['G3'] = $G3;
        $layers['T'] = $G3;
    }
    return $layers;
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

function embankmentFillConsolidationTime($heightEmbankmentH, $depthGWTLayerdw, $coefficientVolumeMv, $PermeabilityK, $degreeConsolidationU)
{
    $h = $heightEmbankmentH;
    $dw = $depthGWTLayerdw;
    //Characteristic co-efficient of consolidation, cv = (k/(mv*9.81))*1000

    $k = $PermeabilityK;
    $mv = $coefficientVolumeMv;

    //Calculate cv
    $cv = ($k / ($mv * 9.81)) * 1000;

    $U = $degreeConsolidationU;

    if ($U >= 5 && $U <= 55) {
        //Time Factor for given U value, TvU70=(22/28)*(U/100)^2
        $TvU70 = (22.0 / 28.0) * pow(($U / 100), 2);
    } elseif ($U >= 60 && $U <= 95) {
        //Time Factor for given U value, TvU70=1.7813-0.9332*log10(100-U)
        $TvU70 = 1.7813 - 0.9332 * log10(100 - $U);
    }

    //Consolidation time, tU70  = (Tv*h^2)/(cv*3600*24)
    $tU70['days'] = round(($TvU70 * pow($h, 2)) / ($cv * 3600 * 24), 2);
    $tU70['weeks'] = round(($TvU70 * pow($h, 2)) / ($cv * 3600 * 24 * 7), 2);
    $tU70['months'] = round(($TvU70 * pow($h, 2)) / ($cv * 3600 * 24 * 30), 2);

    $data['TvU70'] = round($TvU70, 3);
    $data['tU70'] = $tU70;

    return $data;
}
