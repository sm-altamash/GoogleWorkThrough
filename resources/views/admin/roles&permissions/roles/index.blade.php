@extends('admin.layouts.master')
@section('title', 'Roles')
@section('content')
    <section>
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">User Management /</span> Roles</h4>
        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="table" id="role-datatable">
                    <thead>
                        <tr>
                            <th class="not_include"></th>
                            <th>Sr.No</th>
                            <th>Name</th>
                            <th class="not_include">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Add Role Modal -->
        <div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-add-new-role">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="role-title mb-2">Add New Role</h3>
                            <p class="text-muted">Set role permissions</p>
                        </div>
                        <!-- Add role form -->
                        <form id="addRoleForm" class="row g-3" onsubmit="return false">
                            @csrf
                            <div class="col-12 mb-4">
                                <label class="form-label" for="modalRoleName">Role Name</label>
                                <input type="text" id="modalRoleName" name="name" class="form-control"
                                    placeholder="Enter a role name" tabindex="-1" />
                            </div>
                            <div class="col-12">
                                <h5>Role Permissions</h5>
                                <div class="row pb-2">
                                    <div class="col-12 col-md-5 text-nowrap fw-medium">
                                        Administrator Access
                                        <i class="ti ti-info-circle" data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Allows a full access to the system"></i>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input selectAll" type="checkbox" id="selectAll" />
                                            <label class="form-check-label" for="selectAll"> Select All </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    @foreach ($permissions as $permission)
                                    <div class="col-6 col-md-4 border-top border-1 pt-2 pb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="permission_{{$permission->id}}" name="permissions[]" value="{{$permission->name}}"/>
                                            <label class="form-check-label" for="permission_{{$permission->id}}"> {{$permission->name}}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                <!-- Permission table -->
                                {{-- <div class="table-responsive">
                                    <table class="table table-flush-spacing">
                                        <tbody>
                                            <tr>
                                                <td class="text-nowrap fw-medium">
                                                    Administrator Access
                                                    <i class="ti ti-info-circle" data-bs-toggle="tooltip"
                                                        data-bs-placement="top"
                                                        title="Allows a full access to the system"></i>
                                                </td>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="selectAll" />
                                                        <label class="form-check-label" for="selectAll"> Select All </label>
                                                    </div>
                                                </td>
                                            </tr>
                                            @foreach ($permissions as $permission)
                                                <tr>
                                                    <td class="text-nowrap fw-medium">{{$permission->name}}</td>
                                                    <td>
                                                        <div class="d-flex">
                                                            <div class="form-check me-3 me-lg-5">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="{{str_replace(' ', '', $permission->name)}}Add" />
                                                                <label class="form-check-label" for="{{str_replace(' ', '', $permission->name)}}Add"> Add
                                                                </label>
                                                            </div>
                                                            <div class="form-check me-3 me-lg-5">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="{{str_replace(' ', '', $permission->name)}}Edit" />
                                                                <label class="form-check-label" for="{{str_replace(' ', '', $permission->name)}}Edit"> Edit
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    id="{{str_replace(' ', '', $permission->name)}}Delete" />
                                                                <label class="form-check-label" for="{{str_replace(' ', '', $permission->name)}}Delete">
                                                                    Delete </label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div> --}}
                                <!-- Permission table -->
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                    aria-label="Close">
                                    Cancel
                                </button>
                            </div>
                        </form>
                        <!--/ Add role form -->
                    </div>
                </div>
            </div>
        </div>
        <!--/ Add Role Modal -->

        <!-- Edit Role Modal -->
        <div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-add-new-role">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="role-title mb-2">Add New Role</h3>
                            <p class="text-muted">Set role permissions</p>
                        </div>
                        <!-- Edit role form -->
                        <form id="editRoleForm" class="row g-3" onsubmit="return false">
                            @csrf
                            @method('PUT')
                            <div class="col-12 mb-4">
                                <label class="form-label" for="editName">Role Name</label>
                                <input type="text" id="editName" name="name" class="form-control"
                                    placeholder="Enter a role name" tabindex="-1" />
                            </div>
                            <div class="col-12">
                                <h5>Role Permissions</h5>
                                <div class="row pb-2">
                                    <div class="col-12 col-md-5 text-nowrap fw-medium">
                                        Administrator Access
                                        <i class="ti ti-info-circle" data-bs-toggle="tooltip"
                                            data-bs-placement="top"
                                            title="Allows a full access to the system"></i>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input selectAll" type="checkbox" id="editSelectAll" />
                                            <label class="form-check-label" for="editSelectAll"> Select All </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    @foreach ($permissions as $permission)
                                    <div class="col-6 col-md-4 border-top border-1 pt-2 pb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                id="permission_{{$permission->id}}" name="permissions[]" value="{{$permission->name}}"/>
                                            <label class="form-check-label" for="permission_{{$permission->id}}"> {{$permission->name}}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Update</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                    aria-label="Close">
                                    Cancel
                                </button>
                            </div>
                        </form>
                        <!--/ Edit role form -->
                    </div>
                </div>
            </div>
        </div>
        <!--/ Edit Role Modal -->
    </section>
@endsection
@section('script')
    <script>
        var rowId;
        $(document).ready(function() {
            dataTable = $('#role-datatable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ordering: false,
                ajax: "{{ route('roles.index') }}",
                columns: [{
                        data: ''
                    },
                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: ''
                    }
                ],
                columnDefs: [{
                        // For Responsive
                        className: 'control',
                        orderable: false,
                        searchable: false,
                        responsivePriority: 2,
                        targets: 0,
                        render: function(data, type, full, meta) {
                            return '';
                        }
                    },
                    {
                        // Actions
                        targets: -1,
                        title: 'Actions',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, full, meta) {
                            return (
                                '<a href="javascript:;" class="btn btn-sm btn-icon item-edit" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Edit" onclick="editRole('+full.id+',`' +
                            full.name +
                            '`)"><i class="text-primary ti ti-pencil"></i></a>' +
                                '<a href="javascript:;" class="btn btn-sm btn-icon item-edit" onclick="deleteRole('+full.id+')"><i class="text-danger ti ti-trash"></i></a>'
                            );
                        }
                    },
                    {
                        "defaultContent": "-",
                        "targets": "_all"
                    }
                ],
                order: [
                    [2, 'desc']
                ],
                dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                displayLength: 7,
                lengthMenu: [7, 10, 25, 50, 75, 100],
                buttons: [{
                        extend: 'collection',
                        className: 'btn btn-label-primary dropdown-toggle me-2 waves-effect waves-light',
                        text: '<i class="ti ti-file-export me-sm-1"></i> <span class="d-none d-sm-inline-block">Export</span>',
                        buttons: [{
                                extend: 'print',
                                text: '<i class="ti ti-printer me-1" ></i>Print',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                },
                                customize: function(win) {
                                    //customize print view for dark
                                    $(win.document.body)
                                        .css('color', config.colors.headingColor)
                                        .css('border-color', config.colors.borderColor)
                                        .css('background-color', config.colors.bodyBg);
                                    $(win.document.body)
                                        .find('table')
                                        .addClass('compact')
                                        .css('color', 'inherit')
                                        .css('border-color', 'inherit')
                                        .css('background-color', 'inherit');
                                }
                            },
                            {
                                extend: 'csv',
                                text: '<i class="ti ti-file-text me-1" ></i>Csv',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="ti ti-file-description me-1"></i>Pdf',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                }
                            },
                            {
                                extend: 'copy',
                                text: '<i class="ti ti-copy me-1" ></i>Copy',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)'
                                }
                            }
                        ]
                    },
                    {
                        text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New</span>',
                        className: 'create-new btn btn-primary waves-effect waves-light',
                        attr: {
                            'data-bs-toggle': 'modal',
                            'data-bs-target': '#addRoleModal'
                        },
                    },
                    {
                        text: '<i class="ti ti-license me-sm-1"></i> <span class="d-none d-sm-inline-block">Permissions</span>',
                        className: 'create-new btn btn-primary waves-effect waves-light',
                        action: function(e, dt, node, config) {
                            window.location.href = '{{ route('permissions.index') }}';
                        },
                    }
                ],
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function(row) {
                                var data = row.data();
                                return 'Details of ' + data['full_name'];
                            }
                        }),
                        type: 'column',
                        renderer: function(api, rowIdx, columns) {
                            var data = $.map(columns, function(col, i) {
                                return col.title !==
                                    '' // ? Do not show row in modal popup if title is blank (for check box)
                                    ?
                                    '<tr data-dt-row="' +
                                    col.rowIndex +
                                    '" data-dt-column="' +
                                    col.columnIndex +
                                    '">' +
                                    '<td>' +
                                    col.title +
                                    ':' +
                                    '</td> ' +
                                    '<td>' +
                                    col.data +
                                    '</td>' +
                                    '</tr>' :
                                    '';
                            }).join('');

                            return data ? $('<table class="table"/><tbody />').append(data) : false;
                        }
                    }
                }
            });
            $('div.head-label').html('<h5 class="card-title mb-0">Roles</h5>');

            $(".selectAll").change(function(){
                $("input[type=checkbox]").prop('checked', $(this).prop('checked'));
            });

            // Add Role
            $('#addRoleForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('roles.store') }}",
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    responsive: true,
                    success: function(response) {
                        $('#addRoleForm')[0].reset();
                        $("#addRoleModal").modal("hide");
                        dataTable.ajax.reload();
                        ShowToast('success', response.message);
                    },
                    error: function(jqXHR, exception) {
                        if (jqXHR.status == 422) {
                            $.each(jqXHR.responseJSON.errors, function(index, value) {
                                ShowToast('error', value);
                            });
                        } else {
                            ShowToast('error','An error occured! Please Contact Administrator.');
                        }
                    }
                });
            });

            // Update Role
            $('#editRoleForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ url('roles') }}" + "/" + rowId,
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    responsive: true,
                    success: function(response) {
                        $('#editRoleForm')[0].reset();
                        $("#editRoleModal").modal("hide");
                        dataTable.ajax.reload();
                        ShowToast('success', response.message);
                    },
                    error: function(jqXHR, exception) {
                        if (jqXHR.status == 422) {
                            $.each(jqXHR.responseJSON.errors, function(index, value) {
                                ShowToast('error', value);
                            });
                        } else {
                            ShowToast('error','An error occured! Please Contact Administrator.');
                        }
                    }
                });
            });
        });

        function editRole(id) {
            rowId = id;
            $.ajax({
                url: "{{ url('roles') }}" + "/" + rowId + "/edit",
                type: "GET",
                success: function(response) {
                    var count = 0;
                    $("input[type='checkbox']").prop('checked', false);
                    $('#editName').val(response.role.name);
                    $("#editRoleForm input[type='checkbox']").each(function(i, obj) {
                        if(response.permissions.includes($(obj).val())){
                            $(obj).prop('checked', true);
                        }
                        else{
                            count++;
                        }
                    });
                    if(count == 1){
                        $('#editSelectAll').prop('checked', true);
                    }
                    $('#editRoleModal').modal('show');
                }
            });
        }

        function deleteRole(id){
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                customClass: {
                    confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
                    cancelButton: 'btn btn-label-secondary waves-effect waves-light'
                },
                buttonsStyling: false
            }).then(function (result) {
                if (result.value) {
                    $.ajax({
                        url: "roles/" + id,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            dataTable.ajax.reload();
                            ShowToast('success', response.message);
                        },
                        error: function(jqXHR, exception) {
                            ShowToast('error','An error occured! Please Contact Administrator.');
                        }
                    });
                }
            });
        }
    </script>
@endsection
