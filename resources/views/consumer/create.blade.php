@extends('tablar::page')

@section('content')
<!-- Page header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <!-- Page pre-title -->
                <div class="page-pretitle">
                    Overview
                </div>
                <h2 class="page-title">
                    New Consumer
                </h2>
            </div>
        </div>
    </div>
</div>
<!-- Page body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row row-deck row-cards">
            <div class="col-12">
                <form class="card card-md" action="{{route('consumer.store')}}" method="post" autocomplete="off"
                    novalidate>
                    @csrf
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Create new consumer</h2>
                        {{-- name --}}
                        <div class="mb-3">
                            <label class="form-label">Name:</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                placeholder="Enter name">
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- telephone --}}
                        <div class="mb-3">
                            <label class="form-label">Telephone:</label>
                            <input type="tel" name="telephone"
                                class="form-control @error('telephone') is-invalid @enderror"
                                placeholder="Enter telephone">
                            @error('telephone')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- competitor brands --}}
                        <div class="mb-3">
                            <label class="form-label" for="competitorBrands">Choose a competitor:</label>
                            <select class="form-select @error('competitor_brand_id') is-invalid @enderror"
                                id="competitorBrands" name="competitor_brand_id">
                                @foreach ($competitorBrands as $competitorBrand)
                                <option value="{{$competitorBrand->id}}">{{$competitorBrand->name}}</option>
                                @endforeach
                            </select>
                            @error('competitor_brand_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- franchise --}}
                        <div class="mb-3">
                            <label class="form-label">Franchise:</label>
                            <input type="text" name="franchise"
                                class="form-control @error('franchise') is-invalid @enderror"
                                placeholder="Enter franchise">
                            @error('franchise')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- did he switch --}}
                        <div class="mb-5 mt-5">
                            {{-- <label class="form-label" for="did_he_switch">Did He Switch?</label> --}}
                            <label class="form-check form-switch">
                                Did He Switch?
                                <input class="form-check-input" type="checkbox" aria-label="did_he_switch"
                                    name="did_he_switch" id="did_he_switch" value="1"></label>
                            @error('did_he_switch')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- aspen --}}
                        <div class="mb-3">
                            <label class="form-label">Aspen:</label>
                            <input class="form-radio-input m-0 align-middle" type="radio" id="aspen_menthol_blue"
                                name="aspen" value="aspen_menthol_blue">
                            <label class="m-2" for="aspen_menthol_blue">Aspen menthol blue</label>
                            <input class="form-radio-input m-0 align-middle" type="radio" id="aspen_white" name="aspen"
                                value="aspen_white">
                            <label class="m-2" for="aspen_white">Aspen white</label>
                            @error('aspen')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- packs --}}
                        <div class="mb-3">
                            <label class="form-label">Packs:</label>
                            <input type="number" name="packs" class="form-control @error('packs') is-invalid @enderror"
                                placeholder="Enter packs">
                            @error('packs')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- incentives --}}
                        <div class="mb-3">
                            <label class="form-label">Incentives:</label>
                            <input class="form-radio-input m-0 align-middle" type="radio" id="lvl1" name="incentives"
                                value="lvl1">
                            <label class="m-2" for="lvl1">Level 1</label>
                            <input class="form-radio-input m-0 align-middle" type="radio" id="lvl2" name="incentives"
                                value="lvl2">
                            <label class="m-2" for="lvl2">Level 2</label>
                            @error('incentives')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- refused reason --}}
                        <div class="mb-3">
                            <label class="form-label" for="refusedReason">Reason for refused:</label>
                            <select class="form-select" id="refusedReason" name="reason_for_refusal_ids[]" multiple>
                                @foreach ($refusedReasons as $refusedReason)
                                <option value="{{$refusedReason->id}}">{{$refusedReason->name}}</option>
                                @endforeach
                            </select>
                            @error('reason_for_refusal_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- age --}}
                        <div class="mb-3">
                            <label class="form-label">Age:</label>
                            <input type="number" name="age" class="form-control @error('age') is-invalid @enderror"
                                placeholder="Enter age">
                            @error('age')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- nationality --}}
                        <div class="mb-3">
                            <label class="form-label">Nationality:</label>
                            <input type="text" name="nationality"
                                class="form-control @error('nationality') is-invalid @enderror"
                                placeholder="Enter nationality">
                            @error('nationality')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- gender --}}
                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <input class="form-radio-input m-0 align-middle" type="radio" id="male" name="gender"
                                value="male">
                            <label class="m-2" for="male">Male</label>
                            <input class="form-radio-input m-0 align-middle" type="radio" id="female" name="gender"
                                value="female">
                            <label class="m-2" for="female">Female</label>
                            @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">Create new consumer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
