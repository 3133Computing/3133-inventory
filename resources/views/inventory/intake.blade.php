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
                            <div class="input-group input-group-lg">
                                <input
                                    type="text"
                                    name="barcode"
                                    id="barcode"
                                    value="{{ old('barcode', request('barcode')) }}"
                                    class="form-control"
                                    autocomplete="off"
                                    autofocus
                                    required
                                >
                                <span class="input-group-btn">
                                    <button type="button" id="barcode-lookup-button" class="btn btn-default">
                                        <x-icon type="search" /> {{ trans('general.barcode_lookup_button') }}
                                    </button>
                                </span>
                            </div>
                            <p class="help-block" id="barcode-lookup-status" aria-live="polite"></p>
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
                            <select
                                name="storage_location"
                                id="storage_location"
                                class="form-control input-lg"
                                required
                            >
                                <option value="">{{ trans('general.select_location') }}</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->name }}" {{ old('storage_location') === $location->name ? 'selected' : '' }}>{{ $location->name }}</option>
                                @endforeach
                            </select>
                            <p class="help-block">{{ trans('general.rapid_intake_location_help') }}</p>
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

        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">{{ trans('general.last_10_received_items') }}</h3>
            </div>
            <div class="box-body no-padding">
                @if ($recentReceived->isEmpty())
                    <p class="text-muted" style="padding: 15px;">{{ trans('general.no_recent_received_items') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('general.item') }}</th>
                                    <th>{{ trans('general.qty') }}</th>
                                    <th>{{ trans('general.location') }}</th>
                                    <th>{{ trans('general.move_stock') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentReceived as $received)
                                    @php($item = $received->item)
                                    @if ($item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->name }}</strong><br>
                                                <span class="text-muted">{{ $item->item_no }}</span>
                                            </td>
                                            <td>{{ $received->quantity }}</td>
                                            <td>{{ optional($item->location)->name ?: optional($received->location)->name }}</td>
                                            <td>
                                                <form method="post" action="{{ route('inventory.intake.move', $item) }}" class="form-inline rapid-move-form">
                                                    @csrf
                                                    <select name="storage_location" class="form-control input-sm" required>
                                                        <option value="">{{ trans('general.select_location') }}</option>
                                                        @foreach ($locations as $location)
                                                            <option value="{{ $location->name }}" {{ optional($item->location)->name === $location->name ? 'selected' : '' }}>{{ $location->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-xs btn-primary">{{ trans('general.move_stock') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(function () {
        $('#barcode').focus();

        var lookupUrl = '{{ route('inventory.intake.lookup') }}';
        var lookupRequest;

        function setLookupStatus(message, className) {
            $('#barcode-lookup-status')
                .removeClass('text-muted text-success text-warning text-danger')
                .addClass(className || 'text-muted')
                .text(message || '');
        }

        function lookupBarcode(focusAfterLookup) {
            var barcode = $.trim($('#barcode').val());

            if (! barcode) {
                setLookupStatus('');
                return;
            }

            if (lookupRequest) {
                lookupRequest.abort();
            }

            setLookupStatus('{{ trans('general.barcode_lookup_searching') }}', 'text-muted');
            lookupRequest = $.getJSON(lookupUrl, { barcode: barcode })
                .done(function (response) {
                    if (response.found && response.title) {
                        if (! $.trim($('#name').val())) {
                            $('#name').val(response.title);
                        }

                        if (response.existing) {
                            setLookupStatus('{{ trans('general.barcode_lookup_duplicate_existing') }}'.replace(':item', response.title), 'text-warning');
                        } else {
                            setLookupStatus('{{ trans('general.barcode_lookup_found') }}'.replace(':source', response.source || '{{ trans('general.barcode_lookup_free_source') }}'), 'text-success');
                        }
                    } else {
                        setLookupStatus('{{ trans('general.barcode_lookup_not_found_create_basic') }}', 'text-warning');
                    }
                })
                .fail(function (_xhr, status) {
                    if (status !== 'abort') {
                        setLookupStatus('{{ trans('general.barcode_lookup_failed') }}', 'text-danger');
                    }
                })
                .always(function () {
                    if (focusAfterLookup) {
                        $('#quantity').focus().select();
                    }
                });
        }

        $('#barcode-lookup-button').on('click', function () {
            lookupBarcode(false);
        });

        $('#barcode').on('blur', function () {
            lookupBarcode(false);
        });

        $('#barcode').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                lookupBarcode(true);
            }
        });

        $('#quantity').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $('#storage_location').focus();
            }
        });

        $('#storage_location').on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $('#rapid-intake-form').trigger('submit');
            }
        });
    });
</script>
@stop
