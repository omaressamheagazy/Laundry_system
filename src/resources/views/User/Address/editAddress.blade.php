@extends('layouts.user')


@section('title')
    laudrex
@endsection
@section('front-script')
    <link rel="stylesheet" href="{{ asset('style/assets/css/matrial-switch.css') }}">
@endsection
@section('breadcrumbs')
@endsection
@section('content')
    <div class="content mt-3">
        <div class="animated fadeIn">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">Edit Address</div>
                        <div class="card-body card-block">
                            <form action="{{ route('editAddress', ['id' => $address['id']]) }}" method="post" class="">
                                @csrf
                                <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-addon">phone</div>
                                        <input type="text" id="phone" name="phone" value="{{ $address['phone'] }}"
                                            class="form-control @error('phone')is-invalid @enderror" placeholder="+60">
                                        <div class="input-group-addon"><i class="fa fa-phone"></i></div>
                                        @error('phone')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row form-group">
                                    <div class="col-12">
                                        <input type="hidden" id="latitude" name="lat">
                                        <input type="hidden" id="longitude" name="long">
                                        <div class="form-group">
                                            <label for="autocomplete"class=" form-control-label">Location</label>
                                            <input type="text" id="autocomplete" placeholder="Search" name="address"
                                                class="form-control  @error('address')is-invalid @enderror">
                                                @error('address')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="input-group">
                                        <div id="map" style="height: 500px;width: 100%;z-index:99"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <!-- List group -->
                                    <li class="list-group-item" style="border: none; padding:0">
                                        <label for="someSwitchOptionPrimary">Set as Default address</label>
                                        <div class="material-switch pull-right">
                                            <input type="hidden" name="switchAddress" value="0">
                                            <input id="someSwitchOptionPrimary" name="switchAddress" type="checkbox"
                                                class="btn-primary" value="1" {{ $address['default_address'] == 1 ? 'checked' : '' }}  />
                                            <label for="someSwitchOptionPrimary" class="label-primary btn-success"></label>
                                        </div>
                                    </li>
                                </div>

                                <input type="hidden" name="id" value="{{ Auth::id() }}">
                                <div class="btn-group">
                                    <div class="input-group">
                                        <button type="submit" class="btn btn-success btn-sm">Update</button>
                                    </div>
                                </div>
                                <div class="btn-group">
                                    <div class="input-group">
                                        <a href="{{ route('address') }}" class="btn btn-danger btn-sm"></i>&nbsp;Cancel</a>
                                    </div>
                                </div>

                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- .endrow -->
    </div><!-- .animated -->
    </div><!-- .content -->
@endsection
@section('script')

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="{{ asset('style/assets/js/map.js') }}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API') }}&callback=initMap&v=weekly&libraries=places&region=MY"
        defer></script>



@stop
