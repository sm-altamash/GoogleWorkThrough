@extends('admin.layouts.master')
@section('title', 'Users')
@section('content')
    <section>
        <h4 class="py-3 mb-4"><span class="text-muted fw-light">User Management /</span> Users</h4>
        <div class="card">
            <div class="card-datatable table-responsive pt-0">
                <table class="table" id="role-datatable">
                    <thead>
                        <tr>
                            <th class="not_include"></th>
                            <th>Sr.No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="not_include">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Add New User</h3>
                            <p class="text-muted">Add new user in system.</p>
                        </div>
                        <form id="addUserForm" class="row" onsubmit="return false">
                            @csrf
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label" for="name">User Name</label>
                                <input type="text" id="name" name="name" class="form-control"
                                    placeholder="User Name" autofocus required/>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input type="text" id="email" name="email" class="form-control"
                                    placeholder="User Email" autofocus required/>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label" for="password">Passowrd</label>
                                <input type="password" id="password" name="password" class="form-control"
                                    placeholder="Enter Password" autofocus required/>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label" for="password_confirmation">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                                    placeholder="Confirm Passowrd" autofocus required/>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="roles" class="form-label">Roles</label>
                                <select id="roles" name="role" class="select2 form-select" data-allow-clear="true" data-placeholder="Select Role" required>
                                    <option value=""></option>
                                    @foreach ($roles as $role)
                                        <option value="{{$role->name}}">{{$role->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-center demo-vertical-spacing">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Create User</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                    aria-label="Close">
                                    Discard
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Add User Modal -->

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" role="dialog" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content p-3 p-md-5">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <h3 class="mb-2">Add New User</h3>
                            <p class="text-muted">Add new user in system.</p>
                        </div>
                        <form id="editUserForm" class="row" onsubmit="return false">
                            @csrf
                            @method('PUT')
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label" for="editName">User Name</label>
                                <input type="text" id="editName" name="name" class="form-control"
                                    placeholder="User Name" autofocus required/>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label" for="editEmail">Email</label>
                                <input type="text" id="editEmail" name="email" class="form-control"
                                    placeholder="User Email" autofocus required/>
                            </div>
                            {{-- <div class="col-12 col-md-6 mb-3">
                                <label class="form-label" for="editPassword">Passowrd</label>
                                <input type="password" id="editPassword" name="password" class="form-control"
                                    placeholder="Enter Password" autofocus required/>
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label" for="edit_password_confirmation">Confirm Password</label>
                                <input type="password" id="edit_password_confirmation" name="password_confirmation" class="form-control"
                                    placeholder="Confirm Passowrd" autofocus required/>
                            </div> --}}
                            <div class="col-12 mb-3">
                                <label for="editRoles" class="form-label">Roles</label>
                                <select id="editRoles" name="role" class="select2 form-select" data-allow-clear="true" data-placeholder="Select Role" required>
                                    <option value=""></option>
                                    @foreach ($roles as $role)
                                        <option value="{{$role->name}}">{{$role->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-center demo-vertical-spacing">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1">Update User</button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                    aria-label="Close">
                                    Discard
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Add User Modal -->
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
                ajax: "{{ route('users.index') }}",
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
                        data: 'email'
                    },
                    {
                        data: 'role',
                        name: 'roles.name',
                        render: function(data, type, full, meta) {
                            var roleBadge = '';
                            $(full.roles).each(function(i, role) {
                                roleBadge += '<span class="badge rounded-pill bg-primary">'+role.name+'</span>'
                            });
                            return roleBadge;
                        }
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
                                '<a href="javascript:;" class="btn btn-sm btn-icon item-edit" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Edit" onclick="editUser('+full.id+')"><i class="text-primary ti ti-pencil"></i></a>'
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
                                    columns: ':not(.not_include)',
                                    // // prevent avatar to be display
                                    // format: {
                                    //     body: function(inner, coldex, rowdex) {
                                    //         if (inner.length <= 0) return inner;
                                    //         var el = $.parseHTML(inner);
                                    //         var result = '';
                                    //         $.each(el, function(index, item) {
                                    //             if (item.classList !== undefined && item
                                    //                 .classList.contains('user-name')) {
                                    //                 result = result + item.lastChild
                                    //                     .firstChild.textContent;
                                    //             } else if (item.innerText ===
                                    //                 undefined) {
                                    //                 result = result + item.textContent;
                                    //             } else result = result + item.innerText;
                                    //         });
                                    //         return result;
                                    //     }
                                    // }
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
                                    columns: ':not(.not_include)',
                                    // prevent avatar to be display
                                    // format: {
                                    //     body: function(inner, coldex, rowdex) {
                                    //         if (inner.length <= 0) return inner;
                                    //         var el = $.parseHTML(inner);
                                    //         var result = '';
                                    //         $.each(el, function(index, item) {
                                    //             if (item.classList !== undefined && item
                                    //                 .classList.contains('user-name')) {
                                    //                 result = result + item.lastChild
                                    //                     .firstChild.textContent;
                                    //             } else if (item.innerText ===
                                    //                 undefined) {
                                    //                 result = result + item.textContent;
                                    //             } else result = result + item.innerText;
                                    //         });
                                    //         return result;
                                    //     }
                                    // }
                                }
                            },
                            {
                                extend: 'excel',
                                text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)',
                                    // prevent avatar to be display
                                    // format: {
                                    //     body: function(inner, coldex, rowdex) {
                                    //         if (inner.length <= 0) return inner;
                                    //         var el = $.parseHTML(inner);
                                    //         var result = '';
                                    //         $.each(el, function(index, item) {
                                    //             if (item.classList !== undefined && item
                                    //                 .classList.contains('user-name')) {
                                    //                 result = result + item.lastChild
                                    //                     .firstChild.textContent;
                                    //             } else if (item.innerText ===
                                    //                 undefined) {
                                    //                 result = result + item.textContent;
                                    //             } else result = result + item.innerText;
                                    //         });
                                    //         return result;
                                    //     }
                                    // }
                                }
                            },
                            {
                                extend: 'pdf',
                                text: '<i class="ti ti-file-description me-1"></i>Pdf',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)',
                                    // prevent avatar to be display
                                    // format: {
                                    //     body: function(inner, coldex, rowdex) {
                                    //         if (inner.length <= 0) return inner;
                                    //         var el = $.parseHTML(inner);
                                    //         var result = '';
                                    //         $.each(el, function(index, item) {
                                    //             if (item.classList !== undefined && item
                                    //                 .classList.contains('user-name')) {
                                    //                 result = result + item.lastChild
                                    //                     .firstChild.textContent;
                                    //             } else if (item.innerText ===
                                    //                 undefined) {
                                    //                 result = result + item.textContent;
                                    //             } else result = result + item.innerText;
                                    //         });
                                    //         return result;
                                    //     }
                                    // }
                                }
                            },
                            {
                                extend: 'copy',
                                text: '<i class="ti ti-copy me-1" ></i>Copy',
                                className: 'dropdown-item',
                                exportOptions: {
                                    columns: ':not(.not_include)',
                                    // prevent avatar to be display
                                    // format: {
                                    //     body: function(inner, coldex, rowdex) {
                                    //         if (inner.length <= 0) return inner;
                                    //         var el = $.parseHTML(inner);
                                    //         var result = '';
                                    //         $.each(el, function(index, item) {
                                    //             if (item.classList !== undefined && item
                                    //                 .classList.contains('user-name')) {
                                    //                 result = result + item.lastChild
                                    //                     .firstChild.textContent;
                                    //             } else if (item.innerText ===
                                    //                 undefined) {
                                    //                 result = result + item.textContent;
                                    //             } else result = result + item.innerText;
                                    //         });
                                    //         return result;
                                    //     }
                                    // }
                                }
                            }
                        ]
                    },
                    {
                        text: '<i class="ti ti-plus me-sm-1"></i> <span class="d-none d-sm-inline-block">Add New</span>',
                        className: 'create-new btn btn-primary waves-effect waves-light',
                        attr: {
                            'data-bs-toggle': 'modal',
                            'data-bs-target': '#addUserModal'
                        },
                    }
                ],
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function(row) {
                                var data = row.data();
                                return 'Details of ' + data['name'];
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
            $('div.head-label').html('<h5 class="card-title mb-0">Users</h5>');

            // Add Permission
            $('#addUserForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('users.store') }}",
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    responsive: true,
                    success: function(response) {
                        $('#addUserForm')[0].reset();
                        $("#addUserModal").modal("hide");
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

            // Update User
            $('#editUserForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ url('users') }}" + "/" + rowId,
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    responsive: true,
                    success: function(response) {
                        $('#editUserForm')[0].reset();
                        $("#editUserModal").modal("hide");
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

        function editUser(id) {
            rowId = id;
            $.ajax({
                url: "{{ url('users') }}" + "/" + rowId + "/edit",
                type: "GET",
                success: function(response) {
                    $('#editName').val(response.user.name);
                    $('#editEmail').val(response.user.email);
                    $('#editRoles').val(response.role).select2();
                    $('#editUserModal').modal('show');
                }
            });
        }
    </script>
@endsection
