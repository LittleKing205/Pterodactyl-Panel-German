@extends('layouts.admin')

@section('title')
    Edit Domain
@endsection

@section('content-header')
    <h1>Edit Domain
        <small>You can edit domain.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.subdomain') }}">SubDomain Manager</a></li>
        <li class="active">Edit Domain</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Domain</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.subdomain') }}">
                            <button type="button" class="btn btn-sm btn-primary"
                                    style="border-radius: 0 3px 3px 0;margin-left:-1px;">Go Back
                            </button>
                        </a>
                    </div>
                </div>
                <form method="post" action="{{ route('admin.subdomain.update', $domain->id)  }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="domain" class="form-label">Domain</label>
                            <input type="text" name="domain" id="domain" class="form-control"
                                   placeholder="example.com" value="{{ $domain->domain }}" />
                        </div>
                        <div class="form-group">
                            <label for="egg_ids" class="form-label">Eggs where enabled this domain</label>
                            <select id="egg_ids" name="egg_ids[]" class="form-control" multiple>
                                @foreach($eggs as $egg)
                                    @if (in_array($egg->id, explode(',', $domain->egg_ids)))
                                        <option value="{{ $egg->id }}" selected>{{ $egg->name }}</option>
                                    @else
                                        <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div id="protocol_settings">
                            <hr>
                            @foreach(explode(',', $domain->egg_ids) as $egg)
                                <div class="row">
                                    <div class="form-group col-md-6 col-xs-12">
                                        <label for="protocol_for_{{ $egg }}" class="form-label">Protocol for <code>{{ $eggs[array_search($egg, array_column(json_decode(json_encode($eggs), true), 'id'))]->name }}</code></label>
                                        <input type="text" name="protocol_for_{{ $egg }}" id="protocol_for_{{ $egg }}" class="form-control" placeholder="For example: _minecraft" value="{{ unserialize($domain->protocol)[$egg] }}" />
                                        <p class="small text-muted no-margin">Please write here SRV protocol or leave empty. (If leave empty subdomain will be: <code>myserver.example.com:25565</code> and if you set protocol: <code>myserver.example.com</code>)</p>
                                    </div>
                                    <div class="form-group col-md-6 col-xs-12">
                                        <label for="protocol_type_for_{{ $egg }}" class="form-label">Protocol Type for <code>{{ $eggs[array_search($egg, array_column(json_decode(json_encode($eggs), true), 'id'))]->name }}</code></label>
                                        <select id="protocol_type_for_{{ $egg }}" name="protocol_type_for_{{ $egg }}" class="form-control">
                                            <option value="none" {{ unserialize($domain->protocol_types)[$egg] == 'none' ? 'selected' : '' }}>none</option>
                                            <option value="tcp" {{ unserialize($domain->protocol_types)[$egg] == 'tcp' ? 'selected' : '' }}>TCP</option>
                                            <option value="udp" {{ unserialize($domain->protocol_types)[$egg] == 'udp' ? 'selected' : '' }}>UDP</option>
                                            <option value="tls" {{ unserialize($domain->protocol_types)[$egg] == 'tls' ? 'selected' : '' }}>TLS</option>
                                       </select>
                                   </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        let egg_ids_select = $('#egg_ids');
        let protocol_settings = $('#protocol_settings');

        let eggs = @json($eggs, JSON_PRETTY_PRINT);
        let protocols = @json(unserialize($domain->protocol), JSON_PRETTY_PRINT);
        let types = @json(unserialize($domain->protocol_types), JSON_PRETTY_PRINT);

        egg_ids_select.select2({
            placeholder: 'Select Eggs',
        });

        egg_ids_select.on('change', function() {
            let egg_ids = egg_ids_select.val();

            protocol_settings.html('<hr>');

            $.each(egg_ids, function (key, item) {
                let value;
                let selected;
                let egg = eggs.find(x => x.id === parseInt(item));

                protocols[parseInt(item)] ? value = protocols[parseInt(item)] : value = '';
                types[parseInt(item)] ? selected = types[parseInt(item)] : selected = '';

                protocol_settings.append('<div class="row">' +
                    '                       <div class="form-group col-md-6 col-xs-12">' +
                    '                            <label for="protocol_for_' + egg['id'] + '" class="form-label">Protocol for <code>' + egg['name'] + '</code></label>' +
                    '                            <input type="text" name="protocol_for_' + egg['id'] + '" id="protocol_for_' + egg['id'] + '" class="form-control" placeholder="For example: _minecraft" value="' + value + '" />' +
                    '                            <p class="small text-muted no-margin">Please write here SRV protocol or leave empty. (If leave empty subdomain will be: <code>myserver.example.com:25565</code> and if you set protocol: <code>myserver.example.com</code>)</p>' +
                    '                        </div>' +
                    '                        <div class="form-group col-md-6 col-xs-12">' +
                    '                            <label for="protocol_type_for_' + egg['id'] + '" class="form-label">Protocol Type for <code>' + egg['name'] + '</code></label>' +
                    '                            <select id="protocol_type_for_' + egg['id'] + '" name="protocol_type_for_' + egg['id'] + '" class="form-control">' +
                    '                                <option value="none" ' + (selected == 'none' ? 'selected' : '') + '>none</option>' +
                    '                                <option value="tcp" ' + (selected == 'tcp' ? 'selected' : '') + '>TCP</option>' +
                    '                                <option value="udp" ' + (selected == 'udp' ? 'selected' : '') + '>UDP</option>' +
                    '                                <option value="tls" ' + (selected == 'tls' ? 'selected' : '') + '>TLS</option>' +
                    '                            </select>' +
                    '                        </div>' +
                    '                    </div>');
            });
        });
    </script>
@endsection
