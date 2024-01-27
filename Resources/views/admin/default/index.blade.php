@extends(AdminTheme::wrapper(), ['title' => __('backups::messages.backups')])

@section('container')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>{!! __('backups::messages.backups') !!}</h4>
                    <div class="card-header-action">
                        <a href="{{ route('admin.backups.create') }}" class="btn btn-icon icon-left btn-primary">
                            <i class="fas fa-solid fa-download"></i>
                            {!! __('backups::messages.create_backup') !!}
                        </a>
                        <button type="button" class="btn btn-icon icon-left btn-primary" data-toggle="modal"
                           data-target="#configModal">
                            <i class="fas fa-solid fa-tools"></i>
                            {!! __('backups::messages.settings') !!}
                        </button>
                        <button type="button" class="btn btn-icon icon-left btn-primary" data-toggle="modal"
                                data-target="#logsModal">
                            <i class="fas fa-solid fa-list"></i>
                            {!! __('backups::messages.logs') !!}
                        </button>
                    </div>

                </div>


                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-striped table-md">
                            <tbody>
                            <tr>
                                <th class="">{!! __('backups::messages.name') !!}</th>
                                <th class="">{!! __('backups::messages.path') !!}</th>
                                <th class="">{!! __('backups::messages.size') !!} ({{ bytesToHuman($files_backups['total_size'] + $db_backups['total_size']) }})</th>
                                <th class="">{!! __('backups::messages.date') !!}</th>
                                <th class="text-right">{!! __('admin.action') !!}</th>
                            </tr>

                            <tr>
                                <th scope="row" colspan="5" class="text-center">{!! __('backups::messages.files') !!} ({{ count($files_backups['files']) }})</th>
                            </tr>
                            @foreach ($files_backups['files'] as $backup)
                                <tr>
                                    <td class="">{{ $backup['name'] }}</td>
                                    <td class="">{{ $backup['path'] }}</td>
                                    <td class="">{{ bytesToHuman($backup['size']) }}</td>
                                    <td class="">{{ $backup['date'] }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.backups.download', $backup['name']) }}"
                                           class="btn btn-primary mr-2" title="{!! __('backups::messages.download') !!}">
                                            <i class="fas fa-download"></i>
                                        </a>

                                        <form action="{{ route('admin.backups.delete', $backup['name']) }}" method="post" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    title="{!! __('backups::messages.delete') !!}"
                                                    onclick="return confirm('{!! __('backups::messages.confirm_delete') !!}')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach

                            <tr>
                                <th scope="row" colspan="5" class="text-center">{!! __('backups::messages.database') !!}  ({{ count($db_backups['files']) }})</th>
                            </tr>


                            @foreach ($db_backups['files'] as $backup)
                                <tr>
                                    <td class="">{{ $backup['name'] }}</td>
                                    <td class="">{{ $backup['path'] }}</td>
                                    <td class="">{{ bytesToHuman($backup['size']) }}</td>
                                    <td class="">{{ $backup['date'] }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.backups.download', $backup['name']) }}"
                                           class="btn btn-primary mr-2" title="{!! __('backups::messages.download') !!}">
                                            <i class="fas fa-download"></i>
                                        </a>

                                        <form action="{{ route('admin.backups.delete', $backup['name']) }}" method="post" style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    title="{!! __('backups::messages.delete') !!}"
                                                    onclick="return confirm('{!! __('backups::messages.confirm_delete') !!}')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-right">

                </div>
            </div>
        </div>
    </div>

    {{--  Config modal  --}}
    <div class="modal fade" id="configModal" tabindex="-1" role="dialog"
         aria-labelledby="configModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="configModalLabel">{!! __('backups::messages.settings') !!}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{route('admin.backups.settings')}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group col-md-12 col-12">
                            <label for="path">{!! __('backups::messages.save_path') !!}</label>
                            <input id="path" type="text" class="form-control" name="path" value="{{ settings('backups::path', dirname(base_path()) . '/backups/wemx') }}" required/>
                        </div>
                        <div class="form-group col-md-12 col-12">
                            <label for="save-count">{!! __('backups::messages.save_count') !!}</label>
                            <input id="save-count" type="number" class="form-control" name="save-count" value="{{ settings('backups::save-count', 10) }}" required/>
                        </div>
                        <div class="form-group col-md-12 col-12">
                            <label for="every-hours">{!! __('backups::messages.every_hours', ['hours' => settings('backups::every-hours', 12)]) !!}</label>
                            <input id="every-hours" type="number" class="form-control" name="every-hours" value="{{ settings('backups::every-hours', 12) }}" required/>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{!! __('admin.close') !!}</button>
                            <button type="submit" class="btn btn-primary">{!! __('backups::messages.save') !!}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .modal-logs {
            height: 75vh;
            overflow-y: scroll !important;
            padding-right: 0 !important;
        }
        pre {
            white-space: pre-wrap;       /* css-3 */
            white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
            white-space: -o-pre-wrap;    /* Opera 7 */
            word-wrap: break-word;       /* Internet Explorer 5.5+ */
        }
    </style>

    {{--  Logs modal  --}}
    <div class="modal fade" id="logsModal" tabindex="-1" role="dialog" aria-labelledby="logsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logsModalLabel">{!! __('backups::messages.logs') !!}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('admin.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body modal-logs">
                    <pre class="text-success">{{ $logs }}</pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{!! __('admin.close') !!}</button>
                    <a href="{{ route('admin.backups.logs-clear') }}" class="btn btn-warning">{!! __('backups::messages.clear_logs') !!}</a>
                </div>

            </div>
        </div>
    </div>


@endsection
