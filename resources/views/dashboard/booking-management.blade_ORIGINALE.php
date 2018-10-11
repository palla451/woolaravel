@extends('dashboard.layouts')

@section('csrf-token')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@push('css')
    <link rel="stylesheet" href="{{ url('/') }}/plugins/pace/pace.min.css">
    <link rel="stylesheet" href="{{ url('/') }}/toastr/toastr.min.css">
    <link rel="stylesheet" href="{{ url('/') }}/datatables/datatables.min.css">
    <link rel="stylesheet" href="{{ url('/') }}/sweetalert2/sweetalert2.min.css">
    <link rel="stylesheet" href="{{ url('/') }}/plugins/daterangepicker/daterangepicker.css">
@endpush

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="active"><a href="{{ url('/') }}"><i class="fa fa-dashboard"></i> {{ __('Home') }}</a></li>
        <li><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="active">{{ __('Booking Management') }}</li>
    </ol>
@endsection

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        @permission('read-booking')
                        <li class="active">
                            <a href="#bookings" data-toggle="tab">{{ __('All booking') }}</a>
                        </li>
                        @endpermission
                        @permission('create-booking')
                        <li{!! auth()->user()->canCreateBooking() && !auth()->user()->canReadBooking() ? ' class="active"':'' !!}>
                            <a href="#addNewBooking" data-toggle="tab">{{ __('Make new booking') }}</a>
                        </li>
                        @endpermission
                    </ul>
                    <div class="tab-content">
                        @permission('read-booking')
                        <div class="active tab-pane" id="bookings">
                            <table id="bookingList" class="table table-striped table-bordered" cellspacing="0" width="100%">
                                <thead>
                                <tr>
                                    <th>{{ __('Room Name') }}</th>
                                    <th>{{ __('Booked By') }}</th>
                                    <th>{{ __('Start') }}</th>
                                    <th>{{ __('End') }}</th>
                                    <th>{{ __('Duration') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th style="width: 60px;">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th>{{ __('Room Name') }}</th>
                                    <th>{{ __('Booked By') }}</th>
                                    <th>{{ __('Start') }}</th>
                                    <th>{{ __('End') }}</th>
                                    <th>{{ __('Duration') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>@endpermission
                        @permission('create-booking')
                        <div class="{!! auth()->user()->canCreateBooking() && !auth()->user()->canReadBooking() ? 'active ':'' !!}tab-pane" id="addNewBooking">
                            <form id="search" action="{{ route('bookings.search') }}" method="POST">
                                {{ csrf_field() }}
                                <div class="row">
                                    <div class="col-md-6">
                                        <!-- Date and time range -->
                                        <div class="form-group">
                                            <label>{{ __('Select date and time') }}:</label>

                                            <div class="input-group">
                                                <input type="text" class="form-control pull-right" name="bookingTime" id="bookingTime">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-calendar-check-o"></i>
                                                </div>
                                            </div>
                                            <!-- /.input group -->
                                        </div>


                                        <!-- Material inline 1 -->
                                        <div class="form-check form-check-inline" style="padding-top: 20px">
                                            <input type="radio" class="form-check-input" id="materialInline1" name="inlineMaterialRadiosExample">
                                            <label class="form-check-label" for="materialInline1">Coworking &nbsp;</label>

                                            <input type="radio" class="form-check-input" id="materialInline2" name="inlineMaterialRadiosExample">
                                            <label class="form-check-label" for="materialInline2">DayOffice &nbsp;</label>

                                            <!-- Material inline 3 -->
                                            <input type="radio" class="form-check-input" id="materialInline3" name="inlineMaterialRadiosExample">
                                            <label class="form-check-label" for="materialInline3">Sala riunioni &nbsp;</label>
                                        </div>


                                        <!-- /.form group -->
                                    </div>
                                    <!-- /.col -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>{{ __('Pax') }}</label>
                                            <select class="form-control" style="width: 100%;" name="pax">
                                                <option value="">Please select one</option>
                                                @foreach($rooms as $room)
                                                <option>{{ $room->pax }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>{{ __('Location') }}</label>
                                            <select class="form-control" style="width: 100%;" name="location">
                                                <option value="">Please select one</option>
                                                @foreach($sedi as $sede)
                                                    <option>{{ $sede->sede }}</option>
                                                    @endforeach
                                            </select>
                                        </div>
                                        <!-- /.form-group -->
                                    </div>
                                    <!-- /.col -->
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary" style="width: 100%">{{ __('Submit') }}</button>
                                        </div>
                                        <!-- /.form-group -->
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#addModal" data-whatever="" style="width: 100%">Add Optional</button>
                                        </div>
                                        <!-- /.form-group -->
                                    </div>


                                    <!-- /.col -->
                                </div>
                                <!-- /.row -->
                            </form>
                            <div id="result">
                                <h3>{{ __('Available room') }}:</h3>
                                <table id="searchResult" class="table table-striped table-bordered" cellspacing="0" width="100%">
                                     <thead>
                                     <tr>
                                         <th>{{ __('Room Name') }}</th>
                                         <th>{{ __('Pax') }}</th>
                                         <th>{{ __('Location') }}</th>
                                         <th>{{ __('Type') }}</th>
                                         <th>{{ __('Price') }}</th>
                                         <th>{{ __('Action') }}</th>
                                     </tr>
                                     </thead>
                                     <tfoot>
                                     <tr>
                                         <th>{{ __('Room Name') }}</th>
                                         <th>{{ __('Pax') }}</th>
                                         <th>{{ __('Location') }}</th>
                                         <th>{{ __('Type') }}</th>
                                         <th>{{ __('Price') }}</th>
                                         <th>{{ __('Action') }}</th>
                                     </tr>
                                     </tfoot>
                                </table>
                            </div>
                        </div>@endpermission
                    </div>
                </div>
            </div> <!-- /.col -->
        </div> <!-- /.row -->
    </section>
@endsection

        <!-- Modal form to add a post -->
    <!-- Modal form to add a post -->
    <div id="addModal" class="modal fade bd-example-modal-lg" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="padding: 10px;background-color: #ECF0F5;">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">x</button>
                    <h4 class="modal-title">ADD OPTIONAL</h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" role="form">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Coffee Break">Coffee Break
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="coffee_break" autofocus>
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="euro 8,5 a persona">Quick Lunch
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="quick_lunch" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Videoproiettore">Videoproiettore
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="videoproiettore" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Permanent Coffee">Permanent Coffee
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="permanent_coffee" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Wi-Fi">Wi-Fi
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="wifi" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Videoconferenza">Videoconferenza
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="videoconferenza" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Webconference">Webconference
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="webconference" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Lavagna Fogli Mobili">Lavagna Fogli mobili
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="lavagna_foglimobili" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Stampante">Stampante
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="stampante" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Permanent Coffee Plus">Permanent Coffee Plus
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="permanent_coffeeplus" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Connessione via cavo">Connessione_viacavo
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="connessione_viacavo" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Integrazione permanent Coffee Plus">Int. Permanent Coffee Plus
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="integrazione_permanentcoffee" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Upgrade Banda 10mb">Upgrade Banda 10 Mb
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="upgrade_banda10mb" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Upgrade Banda 8mb">Upgrade Banda 8 Mb
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="upgrade_banda8mb" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Upgrade Banda 20mb">Upgrade Banda 20 Mb
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="upgrade_banda20mb" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Wirless 4mb 20accessi">Wirless 4mb 20accessi
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="wirless_4mb20accessi" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Wirless 8mb 35accessi">Wirless 4mb 35accessi
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="wirless_8mb35accessi" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Wirless 8mb 35accessi">Wirless 4mb 35accessi
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="wirless_8mb35accessi" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Videoregistrazione">Videoregistraione
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="videoregistrazione" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Fattorino">Fattorino
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="fattorino" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>

                                <div class="col-md-4">
                                    <a type="text" data-toggle="tooltip" data-placement="top" title="Lavagna Interattiva">Lavagna Interattiva
                                        <i class="glyphicon glyphicon-question-sign"></i>
                                    </a>
                                    <input type="number" class="form-control" id="lavagna_interattiva" autofocus>
                                    <p class="errorContent text-center alert alert-danger hidden"></p>
                                </div>


                            </div>
                        </div>



                    </form>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success add" data-dismiss="modal">
                            <span id="" class='glyphicon glyphicon-check'></span> Add
                        </button>
                        <button type="button" class="btn btn-warning" data-dismiss="modal">
                            <span class='glyphicon glyphicon-remove'></span> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>



@push('js')
    <script src="{{ url('/') }}/plugins/pace/pace.min.js"></script>
    <script src="{{ url('/') }}/toastr/toastr.min.js"></script>
    <script src="{{ url('/') }}/toastr/option.js"></script>
    <script src="{{ url('/') }}/datatables/datatables.min.js"></script>
    <script src="{{ url('/') }}/sweetalert2/sweetalert2.min.js"></script>
    <script src="{{ url('/') }}/plugins/daterangepicker/moment.min.js"></script>
    <script src="{{ url('/') }}/plugins/daterangepicker/daterangepicker.js"></script>
    <script>
        $(document).ajaxStart(function() { Pace.restart(); });
        $('#result').hide(); // hide booking search result table

        $(document).ready(function () {

            $('#bookingList').DataTable({
                initComplete: function(){
                    var api = this.api();
                    $('#bookingList_filter input')
                        .off('.DT')
                        .on('keyup.DT', function (e) {
                            if (e.keyCode === 13) {
                                api.search(this.value).draw();
                            }
                        });
                },
                processing: true,
                serverSide: true,
                ajax: "{!! route('datatables.bookings') !!}",
                lengthMenu: [[5,20,50,100,-1], [5,20,50,100,"All"]],
                columns: [
                    { data: 'room_name', name: 'room_name' },
                    { data: 'booked_by', name: 'booked_by' },
                    { data: 'start_date', name: 'start_date' },
                    { data: 'end_date', name: 'end_date' },
                    { data: 'duration', name: 'duration'},
                    { data: 'status', name: 'status'},
                    { data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            // Cancel booking
            $('#bookingList')
                .DataTable()
                .on('click', '.btn-delete', function (event) {
                    event.preventDefault();

                    var url = $(this).data('remote');
                    var token = $('meta[name="csrf-token"]').attr('content');

                    swal({
                        title: '{{ __ ('Are you sure to cancel this booking?') }}',
                        text: "{!! __("You won't be able to revert this!") !!}",
                        type: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#ccc',
                        confirmButtonText: '{!! __('Yes, cancel it!') !!}',
                        cancelButtonText: '{!! __('Cancel') !!}',
                        allowOutsideClick: false
                    }).then(function () {
                        $.ajax({
                            type: "POST",
                            url: url,
                            data: {'_method' : 'DELETE', '_token' : token},
                            dataType: 'json'
                        })
                        .done(function(data){
                            swal({
                                title: '{{ __('Cancelled!') }}',
                                text: data.message,
                                type: 'success',
                                confirmButtonText: '{!! __('Close') !!}',
                                allowOutsideClick: false
                            }).then(function(){
                                $('#bookingList').DataTable().ajax.reload();
                            });
                        })
                        .fail(function(data){
                            var errors = data.responseJSON;
                            if (data.status === 403) {
                                swal({
                                    title: '{{ __('Request denied!') }}',
                                    text: errors.message,
                                    type: 'error',
                                    confirmButtonText: '{!! __('Close') !!}',
                                    allowOutsideClick: false
                                });
                            } else {
                                $.each(errors.errors, function (key, value) {
                                    toastr.error(value);
                                });
                            }
                        });
                    });
                });

            // Date range picker with time picker
            $('#bookingTime').daterangepicker({
                timePicker: true,
                timePickerIncrement: 15,
                timePicker24Hour: true,
                minDate: moment().format('DD/MM/YYYY HH'),
                opens: 'right',
                locale: {
                    format: 'DD/MM/YYYY HH:mm:ss'
                }
            });

            $('#search').on('submit', function (event) {
                event.preventDefault();
                var data = $(this).serialize();
                var bookingTime = document.getElementById('bookingTime').value;

              //  alert(bookingTime);

                $.ajax({
                    type: "POST",
                    url: "{{ route('bookings.search') }}",
                    data: data,
                    dataType: 'json'
                })
                .done(function(result){
                    $('#searchResult').DataTable().destroy();
                    $('#result').show();
                    $('#searchResult').DataTable({
                        data: result,
                        columns: [
                            { data: 'name' },
                            { data: 'pax', width: '100px', orderable: false, searchable: false},
                            { data: 'location', width: '100px', orderable: false, searchable: false},
                            { data: 'type', width: '100px', orderable: false, searchable: false},
                            { data: 'price', width: '100px', orderable: false, searchable: false},
                            { data: 'action', width: '100px', orderable: false, searchable: false}
                        ]
                    }).on('click', '.btn-book', function(event){
                        event.preventDefault();

                        var roomName = $(this).data('name');
                        var roomId = $(this).data('id');
                        var url = $(this).data('remote');
                        var token = $('meta[name="csrf-token"]').attr('content');
                        var clickedRow = $('#searchResult')
                                            .DataTable()
                                            .row($(this).parents('tr'));

                        swal({
                            title: roomName,
                            text: "{!! __("Are you sure to book this room?") !!}",
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#ccc',
                            confirmButtonText: "{!! __("Yes, book it!") !!}",
                            cancelButtonText: '{!! __('Cancel') !!}',
                            allowOutsideClick: false
                        })
                        .then(function(){
                            var input = {
                                '_token' : token,
                                'roomId' : roomId,
                                'roomName' : roomName,
                                'bookingTime': bookingTime
                            };
                            $.ajax({
                                type: "POST",
                                url: url,
                                data: input,
                                dataType: 'json'
                            })
                            .done(function(data){
                                swal({
                                    title: '{{ __('Booked!') }}',
                                    text: data.message,
                                    type: 'success',
                                    allowOutsideClick: false
                                }).then(function(){
                                    clickedRow.remove().draw();
                                    $('#bookingList').DataTable().ajax.reload();
                                });
                            })
                            .fail(function(data){
                                var errors = data.responseJSON;
                                $.each(errors.errors, function (key, value) {
                                    toastr.error(value);
                                });
                            });
                        });
                    });
                })
                .fail(function(data){
                    $('#result').hide();

                    var errors = data.responseJSON;
                    $.each(errors.errors, function (key, value) {
                        toastr.error(value);
                    });
                });
            });

        });
    </script>
@endpush