@extends('layouts.admin')

@section('title')
    Create Domain
@endsection

@section('content-header')
    <h1>Create Domain
        <small>You can create domain.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.subdomain') }}">SubDomain Manager</a></li>
        <li class="active">Create Domain</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Create Domain</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.subdomain') }}">
                            <button type="button" class="btn btn-sm btn-primary"
                                    style="border-radius: 0 3px 3px 0;margin-left:-1px;">Go Back
                            </button>
                        </a>
                    </div>
                </div>
                <form method="post" action="{{ route('admin.subdomain.create')  }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="domain" class="form-label">Domain</label>
                            <input type="text" name="domain" id="domain" class="form-control"
                                   placeholder="example.com" />
                        </div>
                        <div class="form-group">
                            <label for="egg_ids" class="form-label">Eggs where enabled this domain</label>
                            <select id="egg_ids" name="egg_ids[]" class="form-control" multiple>
                                @foreach($eggs as $egg)
                                    <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="protocol_settings"></div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Create</button>
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

        egg_ids_select.select2({
            placeholder: 'Select Eggs',
        });

        egg_ids_select.on('change', function() {
           let egg_ids = egg_ids_select.val();

            protocol_settings.html('<hr>');

            $.each(egg_ids, function (key, item) {
               let egg = eggs.find(x => x.id === parseInt(item));

               protocol_settings.append('<div class="row">' +
               '                            <div class="form-group col-md-6 col-xs-12">' +
               '                                <label for="protocol_for_' + egg['id'] + '" class="form-label">Protocol for <code>' + egg['name'] + '</code></label>' +
               '                                <input type="text" name="protocol_for_' + egg['id'] + '" id="protocol_for_' + egg['id'] + '" class="form-control" placeholder="For example: _minecraft" />' +
               '                                <p class="small text-muted no-margin">Please write here SRV protocol or leave empty. (If leave empty subdomain will be: <code>myserver.example.com:25565</code> and if you set protocol: <code>myserver.example.com</code>)</p>' +
               '                            </div>' +
               '                            <div class="form-group col-md-6 col-xs-12">' +
               '                                <label for="protocol_type_for_' + egg['id'] + '" class="form-label">Protocol Type for <code>' + egg['name'] + '</code></label>' +
               '                                <select id="protocol_type_for_' + egg['id'] + '" name="protocol_type_for_' + egg['id'] + '" class="form-control">' +
               '                                    <option value="none">none</option>' +
               '                                    <option value="tcp">TCP</option>' +
               '                                    <option value="udp">UDP</option>' +
               '                                    <option value="tls">TLS</option>' +
               '                                </select>' +
               '                            </div>' +
               '                        </div>');
           });
        });
    </script>
@endsection
