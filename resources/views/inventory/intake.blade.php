@extends('layouts/default')

@section('title')
    {{ trans('general.rapid_intake') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('inventory.scan') }}" class="btn btn-default pull-right">
        <x-icon type="search" /> {{ trans('general.inventory_scan') }}
    </a>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('general.rapid_intake') }}</h2>
            </div>
            <form method="post" action="{{ route('inventory.intake.store') }}" id="rapid-intake-form" class="form-horizontal">
                @csrf
                <div class="box-body">
                    <p class="text-muted">{{ trans('general.rapid_intake_help') }}</p>

                    <div class="form-group {{ $errors->has('barcode') ? ' has-error' : '' }}">
                        <label for="barcode" class="col-md-3 control-label">{{ trans('general.barcode') }}</label>
                        <div class="col-md-9">
                            <input
                                type="text"
                                name="barcode"
                                id="barcode"
                                value="{{ old('barcode', request('barcode')) }}"
                                class="form-control input-lg"
                                autocomplete="off"
                                autofocus
                                required
                            >
                            {!! $errors->first('barcode', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('quantity') ? ' has-error' : '' }}">
                        <label for="quantity" class="col-md-3 control-label">{{ trans('general.qty') }}</label>
                        <div class="col-md-9">
                            <input
                                type="number"
                                name="quantity"
                                id="quantity"
                                value="{{ old('quantity', 1) }}"
                                min="1"
                                max="99999"
                                class="form-control input-lg"
                                required
                            >
                            {!! $errors->first('quantity', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('storage_location') ? ' has-error' : '' }}">
                        <label for="storage_location" class="col-md-3 control-label">{{ trans('general.storage_location') }}</label>
                        <div class="col-md-9">
                            <input
                                type="text"
                                name="storage_location"
                                id="storage_location"
                                value="{{ old('storage_location') }}"
                                class="form-control input-lg"
                                autocomplete="off"
                                list="rapid-intake-locations"
                                required
                            >
                            <datalist id="rapid-intake-locations">
                                @foreach (\App\Models\Location::query()->orderBy('name')->limit(50)->pluck('name') as $locationName)
                                    <option value="{{ $locationName }}">
                                @endforeach
                            </datalist>
                            {!! $errors->first('storage_location', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('name') ? ' has-error' : '' }}">
                        <label for="name" class="col-md-3 control-label">{{ trans('general.item_name') }}</label>
                        <div class="col-md-9">
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name') }}"
                                class="form-control"
                                autocomplete="off"
                                placeholder="{{ trans('general.barcode') }}"
                            >
                            <p class="help-block">{{ trans('general.rapid_intake_name_help') }}</p>
                            {!! $errors->first('name', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                        </div>
                    </div>
                </div>
                <div class="box-footer text-right">
                    <button type="submit" class="btn btn-success btn-lg">
                        <x-icon type="checkmark" /> {{ trans('general.receive_stock') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.rapid_intake_fast_flow') }}</h3>
            </div>
            <div class="box-body">
                <ol>
                    <li>{{ trans('general.rapid_intake_step_scan') }}</li>
                    <li>{{ trans('general.rapid_intake_step_quantity') }}</li>
                    <li>{{ trans('general.rapid_intake_step_location') }}</li>
                    <li>{{ trans('general.rapid_intake_step_enter') }}</li>
                </ol>
                <p class="text-muted">{{ trans('general.rapid_intake_existing_new_help') }}</p>
            </div>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(function () {
        $('#barcode').focus();
    });
</script>
@stop
