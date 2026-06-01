@extends('layouts/default')

@section('title')
    {{ trans('general.inventory_scan') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('consumables.create') }}" class="btn btn-primary pull-right">
        <x-icon type="create" /> {{ trans('general.create') }} {{ trans('general.consumable') }}
    </a>
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('general.inventory_scan') }}</h2>
            </div>
            <div class="box-body">
                <p class="text-muted">
                    {{ trans('general.inventory_scan_help') }}
                </p>

                <form id="inventory-scan-form" method="post" action="{{ route('inventory.scan.lookup') }}">
                    @csrf
                    <div class="input-group input-group-lg">
                        <input
                            type="text"
                            name="barcode"
                            id="inventory-barcode"
                            class="form-control"
                            autocomplete="off"
                            autofocus
                            placeholder="{{ trans('general.barcode') }}"
                        >
                        <span class="input-group-btn">
                            <button class="btn btn-primary" type="submit">
                                <x-icon type="search" /> {{ trans('general.search') }}
                            </button>
                        </span>
                    </div>
                </form>

                <div id="inventory-scan-result" class="well" style="display:none; margin-top:20px;"></div>
            </div>
        </div>
    </div>
</div>
@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(function () {
        var $form = $('#inventory-scan-form');
        var $barcode = $('#inventory-barcode');
        var $result = $('#inventory-scan-result');

        $barcode.focus();

        $form.on('submit', function (event) {
            event.preventDefault();

            $.ajax({
                method: 'POST',
                url: $form.attr('action'),
                data: $form.serialize(),
                success: function (item) {
                    var html = '<h3>' + $('<div>').text(item.name || item.asset_tag).html() + '</h3>';

                    if (item.type === 'consumable') {
                        html += '<p><strong>{{ trans('general.barcode') }}:</strong> ' + $('<div>').text(item.barcode).html() + '</p>';
                        html += '<p><strong>{{ trans('general.qty') }}:</strong> ' + item.quantity + ' &nbsp; <strong>{{ trans('general.remaining') }}:</strong> ' + item.remaining + '</p>';
                        html += '<p>';
                        html += '<a class="btn btn-default" href="' + item.url + '">{{ trans('general.view') }}</a> ';
                        html += '<form method="post" action="' + item.receive_url + '" style="display:inline-block; margin-left:5px;">@csrf <input type="hidden" name="quantity" value="1"><button class="btn btn-success" type="submit">{{ trans('general.receive_stock') }} +1</button></form> ';
                        html += '<form method="post" action="' + item.remove_url + '" style="display:inline-block; margin-left:5px;">@csrf <input type="hidden" name="quantity" value="1"><button class="btn btn-warning" type="submit">{{ trans('general.remove_stock') }} -1</button></form>';
                        html += '</p>';
                    } else {
                        html += '<p><strong>{{ trans('general.asset_tag') }}:</strong> ' + $('<div>').text(item.asset_tag).html() + '</p>';
                        if (item.serial) {
                            html += '<p><strong>{{ trans('admin/hardware/form.serial') }}:</strong> ' + $('<div>').text(item.serial).html() + '</p>';
                        }
                        html += '<p><a class="btn btn-default" href="' + item.url + '">{{ trans('general.view') }}</a></p>';
                    }

                    $result.removeClass('alert alert-danger').html(html).show();
                    $barcode.val('').focus();
                },
                error: function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ trans('general.not_found') }}';
                    $result.addClass('alert alert-danger').text(message).show();
                    $barcode.focus().select();
                }
            });
        });
    });
</script>
@stop
